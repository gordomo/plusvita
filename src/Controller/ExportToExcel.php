<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/excel-helper")
 */
Class ExportToExcel extends AbstractController {

    public static function toExcel($html, $router, $nombre) {
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
            $spreadsheet = $reader->loadFromString($html);

            $colums = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
            foreach ($colums as $colum) {
                $spreadsheet->getActiveSheet()->getColumnDimension($colum)->setAutoSize(true);
            }
            $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(40);
            $spreadsheet->getActiveSheet()->getDefaultRowDimension()->setRowHeight(20);
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

            $temp_file = tempnam(sys_get_temp_dir(), $nombre);

            // Create the file
            $writer->save($temp_file);
            $getExcelRoute = $router->generate('helper_get_excel', ['path' => explode('/', $temp_file)[2], 'nombre' => $nombre]);

            return new JsonResponse(['error' => false, 'message' => $getExcelRoute]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => true, 'code' => $e->getCode(), 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/getExcel/tmp/{path}/{nombre}", name="helper_get_excel", methods={"GET"})
     */

    public function getExcel(Request $request, $path, $nombre): BinaryFileResponse
    {
        $filename = '/tmp/'.$path;
        // This should return the file to the browser as response
        $response = new BinaryFileResponse($filename);

        // To generate a file download, you need the mimetype of the file
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        // Set the mimetype with the guesser or manually
        if($mimeTypeGuesser->isGuesserSupported()){
            // Guess the mimetype of the file according to the extension of the file
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType($filename));
        }else{
            // Set the mimetype of the file manually, in this case for a text file is text/plain
            $response->headers->set('Content-Type', 'text/plain');
        }

        $filenameFallback = preg_replace(
            '#^.*\.#',
            md5($filename) . '.', $filename
        );


        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $nombre
        );

        $response->headers->set('Content-Disposition', $disposition);
        return $response;

    }
}