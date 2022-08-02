<?php

namespace App\Controller;


use App\Repository\ClienteRepository;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Bundle\SnappyBundle\Snappy\Response\JpegResponse;


/**
 * @Route("/imprimir")
 */
class ImprimirController extends AbstractController
{
    /**
     * @Route("/", name="imprimir", methods={"POST", "GET"})
     */
    public function index(Request $request): Response
    {
        $html = $request->request->get('htmlToPrint');
        $htmlToPrint = $this->render('imprimir/index.html.twig', [
            'html' => $html,
        ]);

    }

    /**
     * @Route("/pdf", name="imprimir_pdf", methods={"POST", "GET"})
     */
    public function pdf(Request $request, Pdf $knpSnappyPdf): Response
    {
        $html = $request->get('html');
        $head = $request->get('header');
        $headStyles = $request->get('headStyles');

/*        return $this->render('cliente/_historia_print.html.twig', [
            'html'  => $html,
        ]);*/

        $html_to_print = $html;

        $header = <<<HTML
<!DOCTYPE html>
<html>
  $headStyles
  <body style="margin-bottom: 5px">
    $head
  </body>
</html>
HTML;

        $footer = <<<HTML
<!DOCTYPE html>
<html>
  <head>
  <style type="text/css">
    div { float: right; font-size: 10px; width: 125px; }
  </style>
  <script>
  function subst() {
      var vars = {};
      var query_strings_from_url = document.location.search.substring(1).split('&');
      for (var query_string in query_strings_from_url) {
          if (query_strings_from_url.hasOwnProperty(query_string)) {
              var temp_var = query_strings_from_url[query_string].split('=', 2);
              vars[temp_var[0]] = decodeURI(temp_var[1]);
          }
      }
      var css_selector_classes = ['page', 'frompage', 'topage', 'webpage', 'section', 'subsection', 'date', 'isodate', 'time', 'title', 'doctitle', 'sitepage', 'sitepages'];
      for (var css_class in css_selector_classes) {
          if (css_selector_classes.hasOwnProperty(css_class)) {
              var element = document.getElementsByClassName(css_selector_classes[css_class]);
              for (var j = 0; j < element.length; ++j) {
                  element[j].textContent = vars[css_selector_classes[css_class]];
              }
          }
      }
  }
  </script>
  </head>
  <body onload="subst()">
    <table style="border-bottom: 1px solid black; width: 100%">
        <tr>
          <td style="text-align:right">
            Pagina <span class="page"></span> de <span class="topage"></span>
          </td>
        </tr>
    </table>
  </body>
</html>
HTML;

        $options = [
            'header-html' => $header,
            'footer-html' => $footer,
            'page-size' => 'A4',
            'margin-bottom' => '5',
            'no-custom-header-propagation' => true,
            'encoding' => 'UTF-8'
        ];

        $knpSnappyPdf->setTimeout(60000000000)->generateFromHtml($html, '/var/www/html/var/cache/dev/snappy/bill-123.pdf', $options, true);

        $error = false;

        if(file_exists('/var/www/html/var/cache/dev/snappy/bill-123.pdf')) {
            $fileName = 'historia' . date('d-m-y-h-i-s') . '.pdf';
            copy('/var/www/html/var/cache/dev/snappy/bill-123.pdf', '/var/www/html/public/uploads/snappy/'.$fileName);
            unlink('/var/www/html/var/cache/dev/snappy/bill-123.pdf');
        } else {
            $error = true;
        }

        return new JsonResponse(['url' => '/uploads/snappy/'.$fileName, 'error' => $error]);

    }

    /**
     * @Route("/pdf2", name="imprimir_pdf2", methods={"POST", "GET"})
     */
    public function pdf2(Request $request, Pdf $knpSnappyPdf, ClienteRepository $clienteRepository): Response
    {
        $seccionesSelected = $request->get('seccionesSelected');
        $clientId = $request->get('clientId');
        $cliente = $clienteRepository->find($clientId);

        $ingresoHtml = '';

        foreach ($seccionesSelected as $seccion) {
            switch ($seccion) {
                case 'historia-al-ingreso':
                    $ingresoHtml = $this->getIngresoHtml($cliente);
                    break;
                case 'notas':
                    break;
                case 'notas-en-turnos':
                    break;
                case 'evoluciones-imprimir':
                    break;
                case 'novedades':
                    break;
                case 'epicrisis-al-alta':
                    break;
            }
        }

        $options = [
            'page-size' => 'A4',
            'margin-bottom' => '5',
            'no-custom-header-propagation' => true,
            'encoding' => 'UTF-8'
        ];

        $fileName = 'historia' . date('d-m-y-h-i-s') . '.pdf';

        $knpSnappyPdf->generateFromHtml($ingresoHtml, '/var/www/html/public/uploads/snappy/'.$fileName, $options, true);

        return new JsonResponse('/var/www/html/public/uploads/snappy/'.$fileName);
    }

    private function getIngresoHtml($cliente) {
        $ingreso = $cliente->getHistoriaIngreso();
        $ingresoHtml = '';
        if (!empty($ingreso)) {
            if (!empty($ingreso->getExamenesComplementeriosFiles())) {
                foreach ($ingreso->getExamenesComplementeriosFiles() as $examenes) {
                    $url = 'uploads/adjuntos/pacientes/' . $cliente->getId() . "/complementerios/" . $examenes;
                    $link = $this->generateUrl('download_pdf_adjunto', ['path'=> $url, 'nombre'=> $examenes]);
                    $linksComplementarios = "<a href='$link'>$examenes</a><br>";
                }
            } else {
                $linksComplementarios = 'No tiene';
            }

            $antecedentesTexto = $ingreso->getAntecedentesTexto();
            $enfermedadActual = $ingreso->getEnfermedadActual();
            $fisico = $ingreso->getExamenFisicoAlIngreso();
            $desc = $ingreso->getExamenComplementarioDesc();
            $indicaciones = $ingreso->getIndicaciones();


            $ingresoHtml = <<<HTML
                <tr>
                    <th>Antecedentes:</th>
                </tr>
                <tr>
                    <td>$antecedentesTexto</td>
                </tr>
                <tr>
                    <th>Enfermedad Actual:</th>
                </tr>
                <tr>
                    <td>$enfermedadActual</td>
                </tr>
                <tr>
                    <th>Exámen Físico al ingreso:</th>
                </tr>
                <tr>
                    <td>$fisico</td>
                </tr>
                <tr>
                    <th>Exámen Complementario:</th>
                </tr>
                <tr>
                    <td>$desc</td>
                </tr>
                <tr>
                    <th>Indicaciones:</th>
                </tr>
                <tr>
                    <td>$indicaciones</td>
                </tr>
                <tr>
                    <th>Adjuntos:</th>
                    <td>$linksComplementarios</td>
                </tr>
HTML;

        }
        
        
        $return = <<<HTML
<div class="row">
        <div class="col-12 historia-al-ingreso" >
            <h4 style="border-top: 1px solid; padding-top: 10px">Ingreso</h4>
            <table class="table table-responsive table-striped">
                $ingresoHtml
            </table>
        </div>
HTML;


        return $return;

    }

}