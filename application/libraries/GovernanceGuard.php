<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Governance Guard
 *
 * Security boundary for the immutable governance identity.
 *
 * Two independent database facts must agree:
 *
 *     users.is_immutable
 *              +
 *     super_admin_identity
 *
 * Rules:
 *
 * 1. users.is_immutable is authoritative for protecting the user
 *    record itself.
 *
 * 2. super_admin_identity is authoritative for identifying the
 *    governance identity.
 *
 * 3. Governance authorization requires both to agree.
 *
 * 4. Any inconsistency fails closed.
 *
 * 5. Security anomalies are recorded through the existing
 *    Audit_log service.
 */
class GovernanceGuard
{
    /**
     * CodeIgniter instance.
     *
     * @var CI_Controller
     */
    protected $CI;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->database();
        $this->CI->load->library('audit_log');
    }

    /**
     * Determine whether a user is the immutable governance identity.
     *
     * FAIL CLOSED:
     *
     * Any disagreement between users.is_immutable and
     * super_admin_identity causes denial.
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function is_governance_identity($user_id)
    {
        $user_id = (int) $user_id;

        if ($user_id <= 0) {
            return false;
        }

        /*
         * ---------------------------------------------------------------
         * 1. Load user immutable state
         * ---------------------------------------------------------------
         */

        $user_query = $this->CI->db
            ->select('id, username, email, is_immutable')
            ->from('users')
            ->where('id', $user_id)
            ->limit(1)
            ->get();

        if (!$user_query || $user_query->num_rows() !== 1) {

            /*
             * Missing user is not itself an inconsistency we can
             * safely attribute to a governance identity.
             *
             * Deny.
             */
            return false;
        }

        $user = $user_query->row();

        $is_immutable = ((int) $user->is_immutable === 1);

        /*
         * ---------------------------------------------------------------
         * 2. Load the complete governance identity registry
         * ---------------------------------------------------------------
         *
         * We intentionally inspect the entire table rather than only
         * looking for the current user.
         *
         * This allows us to detect:
         *
         *     user 1 immutable = 1
         *     identity belongs to user 2
         *
         * or:
         *
         *     user 1 immutable = 1
         *     identity table contains zero rows
         *
         * or:
         *
         *     user 1 immutable = 0
         *     identity belongs to user 1
         *
         * All of these are security anomalies.
         */

        $identity_query = $this->CI->db
            ->select('user_id')
            ->from('super_admin_identity')
            ->limit(2)
            ->get();

        if (!$identity_query) {

            /*
             * If the governance registry cannot be read,
             * authorization must fail closed.
             */
            log_message(
                'error',
                'GovernanceGuard: unable to read super_admin_identity.'
            );

            return false;
        }

        $identity_count = $identity_query->num_rows();

        /*
         * The database trigger guarantees one row, but the
         * authorization layer must still verify the invariant.
         */

        if ($identity_count !== 1) {

            $this->record_security_anomaly(
                $user_id,
                $is_immutable,
                $identity_count,
                null,
                'Governance identity registry must contain exactly one row'
            );

            return false;
        }

        $identity = $identity_query->row();

        $governance_user_id = (int) $identity->user_id;

        /*
         * ---------------------------------------------------------------
         * 3. Verify governance identity points to an immutable user
         * ---------------------------------------------------------------
         */

        $governance_user_query = $this->CI->db
            ->select('id, is_immutable')
            ->from('users')
            ->where('id', $governance_user_id)
            ->limit(1)
            ->get();

        if (!$governance_user_query || $governance_user_query->num_rows() !== 1) {

            $this->record_security_anomaly(
                $user_id,
                $is_immutable,
                $identity_count,
                $governance_user_id,
                'Governance identity references a missing user'
            );

            return false;
        }

        $governance_user = $governance_user_query->row();

        $governance_user_is_immutable =
            ((int) $governance_user->is_immutable === 1);

        /*
         * ---------------------------------------------------------------
         * 4. Require all governance facts to agree
         * ---------------------------------------------------------------
         */

        $current_user_matches_identity =
            ($governance_user_id === $user_id);

        /*
         * Valid governance state requires:
         *
         *     current user immutable
         *     AND
         *     identity points to current user
         *     AND
         *     identity's user immutable
         */

        if (
            $is_immutable
            && $current_user_matches_identity
            && $governance_user_is_immutable
        ) {
            return true;
        }

        /*
         * ---------------------------------------------------------------
         * 5. Detect security inconsistency
         * ---------------------------------------------------------------
         *
         * Examples:
         *
         * A:
         *     users.is_immutable = 1
         *     no governance identity
         *
         * B:
         *     users.is_immutable = 0
         *     governance identity exists
         *
         * C:
         *     user 1 immutable
         *     governance identity belongs to user 2
         *
         * D:
         *     governance identity points to a non-immutable user
         */

        $inconsistent =
            ($is_immutable !== $current_user_matches_identity)
            || !$governance_user_is_immutable;

        if ($inconsistent) {

            $this->record_security_anomaly(
                $user_id,
                $is_immutable,
                $identity_count,
                $governance_user_id,
                'Governance identity sources disagree'
            );

            return false;
        }

        /*
         * ---------------------------------------------------------------
         * 6. Normal non-governance user
         * ---------------------------------------------------------------
         *
         * Example:
         *
         *     user 2
         *     is_immutable = 0
         *     governance identity = user 1
         *
         * This is normal and is NOT a security anomaly.
         */

        return false;
    }

    /**
     * Record a governance security anomaly.
     *
     * Uses the application's existing centralized Audit_log service.
     *
     * IMPORTANT:
     *
     * Failure to write the audit record NEVER causes the guard to
     * grant access. Authorization has already failed closed.
     *
     * @param int      $user_id
     * @param bool     $is_immutable
     * @param int      $identity_count
     * @param int|null $governance_user_id
     * @param string   $reason
     *
     * @return bool
     */
    protected function record_security_anomaly(
        $user_id,
        $is_immutable,
        $identity_count,
        $governance_user_id,
        $reason
    ) {
        $audit_id = $this->CI->audit_log->log(
            'Governance',
            'security_anomaly',
            $user_id,
            null,
            [
                'event' => 'governance_identity_inconsistency',

                'reason' => $reason,

                'is_immutable' => (bool) $is_immutable,

                'governance_identity_count' =>
                    (int) $identity_count,

                'governance_identity_user_id' =>
                    $governance_user_id !== null
                        ? (int) $governance_user_id
                        : null,

                'security_decision' => 'deny',
            ]
        );

        if ($audit_id === false) {

            /*
             * NEVER allow audit failure to turn a denied request
             * into an allowed request.
             */
            log_message(
                'error',
                'GovernanceGuard: failed to record security anomaly.'
                . ' user_id=' . (int) $user_id
                . ' reason=' . $reason
            );

            return false;
        }

        return true;
    }
}