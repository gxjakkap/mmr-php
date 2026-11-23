<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{DomainError, Upload};
use App\Services\{GarmentService, PaymentService};

/** Streams private images after an authorization check. Files are never directly web-accessible. */
final class MediaController extends Controller
{
    public function garmentImage(string $id): never
    {
        $img = (new GarmentService($this->db))->imageForViewer($this->actor(), $id);
        $path = Upload::path($img['storage_path']);
        if (!$path) throw DomainError::notFound();
        header('Content-Type: ' . $img['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        header('Content-Disposition: inline; filename="image"');
        readfile($path);
        exit;
    }
    public function slip(string $id): never
    {
        $s = (new PaymentService($this->db))->slipForViewer($this->actor(), $id);
        $path = Upload::path($s['storage_path']);
        if (!$path) throw DomainError::notFound();
        header('Content-Type: ' . $s['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=600');
        header('Content-Disposition: inline; filename="slip"');
        readfile($path);
        exit;
    }
}
