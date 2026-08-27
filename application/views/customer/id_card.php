<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <title>ID Card — <?php echo safe_html($customer->name); ?></title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #eee;
            margin: 0;
            padding: 24px;
        }

        .toolbar {
            max-width: 400px;
            margin: 0 auto 16px auto;
            text-align: right;
        }

        .toolbar button {
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            background: #007bff;
            color: #fff;
            cursor: pointer;
        }

        .card {
            width: 400px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
        }

        .card-header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .card-header h1 {
            font-size: 16px;
            margin: 0;
            color: #007bff;
            letter-spacing: 1px;
        }

        .photo-row {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ccc;
            flex-shrink: 0;
        }

        .photo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #999;
            flex-shrink: 0;
            text-align: center;
        }

        .info {
            flex: 1;
        }

        .info .name {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .info .code {
            font-size: 12px;
            color: #555;
            margin: 0 0 8px 0;
        }

        .info dl {
            margin: 0;
            font-size: 12px;
        }

        .info dt {
            color: #777;
            display: inline;
        }

        .info dd {
            display: inline;
            margin: 0 0 0 4px;
        }

        .info dd::after {
            content: "";
            display: block;
        }

        .codes {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px dashed #ccc;
        }

        .codes img {
            max-width: 130px;
            max-height: 90px;
        }

        .codes .missing {
            font-size: 11px;
            color: #999;
        }

        @media print {

            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .card {
                border: none;
                width: auto;
            }

        }

    </style>

</head>
<body>

    <div class="toolbar no-print">
        <button type="button" onclick="window.print();">
            Print / Save as PDF
        </button>
    </div>

    <div class="card">

        <div class="card-header">
            <h1>CUSTOMER IDENTIFICATION</h1>
        </div>

        <div class="photo-row">

            <?php if (!empty($customer->photo_path)): ?>

                <img
                    class="photo"
                    src="<?php echo base_url($customer->photo_path); ?>"
                    alt="<?php echo safe_html($customer->name); ?>"
                >

            <?php else: ?>

                <div class="photo-placeholder">
                    No Photo
                </div>

            <?php endif; ?>


            <div class="info">

                <p class="name">
                    <?php echo safe_html($customer->name); ?>
                </p>

                <?php if (!empty($customer->customer_code)): ?>

                    <p class="code">
                        <?php echo safe_html($customer->customer_code); ?>
                    </p>

                <?php endif; ?>

                <dl>

                    <?php if (!empty($customer->phone)): ?>
                        <div><dt>Phone:</dt> <dd><?php echo safe_html($customer->phone); ?></dd></div>
                    <?php endif; ?>

                    <?php if (!empty($customer->address)): ?>
                        <div><dt>Address:</dt> <dd><?php echo safe_html($customer->address); ?></dd></div>
                    <?php endif; ?>

                </dl>

            </div>

        </div>


        <div class="codes">

            <div>

                <?php if (!empty($customer->qr_code_path)): ?>

                    <img
                        src="<?php echo base_url(
                            'uploads/' . $customer->qr_code_path
                        ); ?>"
                        alt="QR code"
                    >

                <?php else: ?>

                    <p class="missing">No QR code</p>

                <?php endif; ?>

            </div>

            <div>

                <?php if (!empty($customer->barcode_path)): ?>

                    <img
                        src="<?php echo base_url(
                            'uploads/' . $customer->barcode_path
                        ); ?>"
                        alt="Barcode"
                    >

                <?php else: ?>

                    <p class="missing">No barcode</p>

                <?php endif; ?>

            </div>

        </div>

    </div>

</body>
</html>
