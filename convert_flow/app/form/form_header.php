<?php
/**
 * 폼 전용 헤더
 */
if (!defined('_CONVERT_FLOW_')) exit;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($form['name']) ? $form['name'] : '폼'; ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
    <style>
        body {
            padding: 0;
            margin: 0;
            background-color: #f7f7f7;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
        }
        .form-step {
            transition: all 0.3s ease;
        }
        .progress {
            height: 10px;
            border-radius: 0;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            border-radius: 8px 8px 0 0 !important;
        }
        .btn {
            border-radius: 4px;
            padding: 8px 16px;
            transition: all 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .form-control {
            border-radius: 4px;
            padding: 10px 12px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
        }
        .form-check-label {
            cursor: pointer;
        }
    </style>
</head>
<body>