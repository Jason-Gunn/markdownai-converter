<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Pdf
{
    public static function is_available(): bool
    {
        return class_exists('\Dompdf\\Dompdf');
    }

    public static function stream_report_pdf(string $html, string $fileName): bool
    {
        if (! self::is_available()) {
            return false;
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($fileName) . '"');

        echo $dompdf->output();
        return true;
    }
}
