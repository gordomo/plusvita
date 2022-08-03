<?php

namespace App\Controller;


use App\Repository\ClienteRepository;
use App\Repository\DoctorRepository;
use App\Repository\EvolucionRepository;
use App\Repository\HistoriaPacienteRepository;
use App\Repository\ObraSocialRepository;
use App\Repository\UserRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
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
     * @Route("/pdf2", name="imprimir_pdf2", methods={"POST", "GET"})
     */
    public function pdf2(Request $request, ClienteRepository $clienteRepository, HistoriaPacienteRepository $historiaPacienteRepository, ObraSocialRepository $obraSocialRepository, EvolucionRepository $evolucionRepository, DoctorRepository $doctorRepository, UserRepository $userRepository): Response
    {
        $seccionesSelected = $request->get('seccionesSelected');
        $tiposEvolucion = $request->get('filtrarPorTipo');
        $clientId = $request->get('clientId');
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $cliente = $clienteRepository->find($clientId);

        $ingresoHtml = '';
        $notas = '';
        $notasTurno = '';
        $evoluciones = '';
        $novedades = '';
        $epicrisis = '';

        foreach ($seccionesSelected as $seccion) {
            switch ($seccion) {
                case 'historia-al-ingreso':
                    $ingresoHtml = $this->getIngresoHtml($cliente);
                    break;
                case 'notas':
                    $notas = $this->getNotas($cliente);
                    break;
                case 'notas-en-turnos':
                    $notasTurno = $this->getNotasTurno($cliente);
                    break;
                case 'evoluciones-imprimir':
                    $evoluciones = $this->getEvoluciones($cliente, $desde, $hasta, $tiposEvolucion, $evolucionRepository, $doctorRepository, $userRepository);
                    break;
                case 'novedades':
                    $novedades = $this->getNovedades($cliente);
                    break;
                case 'epicrisis-al-alta':
                    $epicrisis = $this->getEpicrisis($cliente);
                    break;
            }
        }

        $header = $this->getHeader($cliente, $historiaPacienteRepository, $obraSocialRepository, $desde, $hasta);
        $footer = $this->getFooter();

        $principio = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Historia</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="http://plusvita.creandosoluciones.com.ar/assets/css/main.css">
    
    <style>
        @page{
            margin-top: 140px; /* create space for header */
            margin-bottom: 10px; /* create space for footer */
            margin-right: 35px;
            margin-left: 10px;
            font-size: 12px;
        }
        header, footer{
            position: fixed;
            left: 0px;
            right: 0px;
        }
        header{
            height: 140px;
            margin-top: -140px;
        }
        footer{
            bottom: 0;
        }
        #footer .page:after { content: counter(page, decimal); }
    </style>
      
</head>
<body>
    $header
    <main>
HTML;
        $fin = <<<HTML
</main>
$footer
</body>
</html>
HTML;

        $html_to_print = $principio . $ingresoHtml . $notas . $notasTurno . $evoluciones . $novedades . $epicrisis . $fin;

        /*return $this->render('cliente/_historia_print.html.twig', [
            'html'  => $html_to_print,
        ]);*/

        $fileName = 'historia-' . $cliente->getApellido() . '.pdf';

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', 'true');
        $pdfOptions->set("isPhpEnabled", true);

        $dompdf = new Dompdf($pdfOptions);

        $dompdf->loadHtml($html_to_print);
        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();
        $output = $dompdf->output();

        // In this case, we want to write the file in the public directory
        $publicDirectory = '/var/www/html/public/uploads/snappy/';
        // e.g /var/www/project/public/mypdf.pdf
        $pdfFilepath =  $publicDirectory . $fileName;

        // Write file to the desired path
        file_put_contents($pdfFilepath, $output);

        return new JsonResponse(['url' => '/uploads/snappy/'.$fileName]);
    }

    private function getIngresoHtml($cliente) {
        $ingreso = $cliente->getHistoriaIngreso();
        $ingresoHtml = '';
        $linksComplementarios = 'No tiene';
        if (!empty($ingreso)) {
            if (!empty($ingreso->getExamenesComplementeriosFiles())) {
                $linksComplementarios = '';
                foreach ($ingreso->getExamenesComplementeriosFiles() as $examenes) {
                    $url = 'uploads/adjuntos/pacientes/' . $cliente->getId() . "/complementerios/" . $examenes;
                    $link = $this->generateUrl('download_pdf_adjunto', ['path'=> $url, 'nombre'=> $examenes]);
                    $linksComplementarios .= "<a href='$link'>$examenes</a><br>";
                }
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
                </tr>
                <tr>
                    <td>$linksComplementarios</td>
                </tr>
HTML;

        }
        $ingresoHtml = <<<HTML
        <div class="row">
        <div class="col-12" >
            <h4 style="border-top: 1px solid; padding-top: 10px">Ingreso</h4>
            <table class="table table-responsive table-striped" style="text-align: left">
                $ingresoHtml
            </table>
        </div>
    </div>
HTML;
        return $ingresoHtml;

    }

    private function getHeader($cliente, $historiaPacienteRepository, $obraSocialRepository, $novedadesDesde, $novedadesHasta) {
        $nombreyApellido = $cliente->getNombreApellido();
        $dni = $cliente->getDni();
        $obraSocialEntity = $obraSocialRepository->find($cliente->getObraSocial());
        $obraSocial = '';
        $nHistoria = $cliente->getHClinica();
        if (!empty ($obraSocialEntity)) {
            $obraSocial = $obraSocialEntity->getNombre();
        }

        $ingreso = ' - ';
        $egreso = ' - ';
        $historiaPaciente = $historiaPacienteRepository->getHistorialDesdeHasta($cliente, $novedadesDesde, $novedadesHasta);
        if (!empty($historiaPaciente[0])) {
            $ingreso = $historiaPaciente[0]->getFechaIngreso() ? $historiaPaciente[0]->getFechaIngreso()->format('Y-m-d') : $cliente->getFIngreso()->format('Y/m/d');
            $egreso = ($historiaPaciente[0]->getFechaEngreso() ? $historiaPaciente[0]->getFechaEngreso()->format('Y-m-d') : $cliente->getFegreso()) ? $cliente->getFegreso()->format('Y/m/d') : ' - ';
        }

        $header = <<<HTML
<header class="">
    <table class="table header">
        <tr>
            <td colspan="">
                Nº Historia: <span class="small">$nHistoria</span>
            </td>
            <td>
                <img style="max-width:80px;" src="http://plusvita.creandosoluciones.com.ar/assets/images/plusVitaLogo.png">
            </td>
        </tr>
        <tr>
            <td>
                Nombre y Apellido: <span class="small">$nombreyApellido</span><br>
                DNI: <span class="small">$dni</span>
            </td>
            <td>
                Ingreso: <span class="small">$ingreso</span><br>
                Egreso: <span class="small">$egreso</span>
            </td>
            <td>
                Obra social: <span class="small">$obraSocial</span><br>
                Período: <span class="small">$novedadesDesde/$novedadesHasta</span>  
            </td>
        </tr>
    </table>
  </header>
HTML;
        return $header;
    }

    private function getFooter() {
        $footer = <<<HTML
<footer id="footer">
    <table style="border-bottom: 1px solid black; width: 100%">
        <tr>
          <td style="text-align:right">
            Pagina <span class="page"></span>
          </td>
        </tr>
    </table>
</footer>
HTML;
        return $footer;
    }

    private function getNotas(?\App\Entity\Cliente $cliente) {

        $notasHTML = '';

        $notasHTML = <<<HTML
<div class="row">
        <div class="col-12 historia-al-ingreso" >
            <h4 style="border-top: 1px solid; padding-top: 10px">Notas</h4>
            <table class="table table-responsive table-striped" style="text-align: left">
                $notasHTML
            </table>
        </div>
</div>
HTML;
        return $notasHTML;
    }

    private function getNotasTurno(?\App\Entity\Cliente $cliente) {
        $notasHTML = '';

        $return = <<<HTML
<div class="row">
        <div class="col-12 historia-al-ingreso" >
            <h4 style="border-top: 1px solid; padding-top: 10px">Notas en Turno</h4>
            <table class="table table-responsive table-striped" style="text-align: left">
                $notasHTML
            </table>
        </div>
</div>
HTML;
        return $return;
    }

    private function getEvoluciones(?\App\Entity\Cliente $cliente, $evolucionesDesde, $evolucionesHasta, $tiposEvolucion, $evolucionRepository, $doctorRepository, $userRepository) {
        $evolucionesHTML = '';

        $evoluciones = $evolucionRepository->findByFechaClienteYtipos($cliente, $evolucionesDesde, $evolucionesHasta, $tiposEvolucion);

        $evArray = [];

        foreach ($evoluciones as $evolucion) {
            $doctor = $doctorRepository->findBy(['email' => $evolucion->getUser()]);
            if (count($doctor) == 0) {
                $doctor = $userRepository->findBy(['email' => $evolucion->getUser()]);
            }
            if (count($doctor) == 0) {
                $doctor = $userRepository->findBy(['user' => $evolucion->getUser()]);
            }
            $firma = '';
            if (count($doctor) > 0) {
                $firma = $doctor[0]->getFirma();
            }
            $evArray[] = ['evolucion' => $evolucion, 'firma' => $firma];
        }

        foreach ($evArray as $evolucion) {

            $evolucionesHTML .= "<tr style='background-color: #dee2e6'>";
                $evolucionesHTML .= "<td>". $evolucion['evolucion']->getFecha()->format('Y-m-d') ."</td>";
                $evolucionesHTML .= "<td>". $evolucion['evolucion']->getTipo()."</td>";
                $evolucionesHTML .= "<td>". $evolucion['evolucion']->getUser()."</td>";
            $evolucionesHTML .= "</tr>";
            $evolucionesHTML .= "<tr>";
                $evolucionesHTML .= "<td colspan='3'>". $evolucion['evolucion']->getDescription()."</td>";
            $evolucionesHTML .= "</tr>";
            $evolucionesHTML .= "<tr>";
            if (!empty($evolucion['firma'])) {
                $evolucionesHTML .= "<td colspan='3'><img style='max-width: 140px;' src='http://plusvita.creandosoluciones.com.ar/uploads/firmas/". $evolucion['firma'] ."'></td>";
            } else {
                $evolucionesHTML .= "<td colspan='3'>Firma no Registrada</td>";
            }
            $evolucionesHTML .= "</tr>";
        }

        $row = <<<HTML
<div class="row">
        <div class="col-12" >
            <h4 style="border-top: 1px solid; padding-top: 10px">Evoluciones</h4>
            <table class="table" style="text-align: left">
                $evolucionesHTML
            </table>
        </div>
</div>
HTML;

        return $row;

    }

    private function getNovedades(?\App\Entity\Cliente $cliente) {
        $novedadesHTML = '';

        $return = <<<HTML
<div class="row">
        <div class="col-12 historia-al-ingreso" >
            <h4 style="border-top: 1px solid; padding-top: 10px">Novedades</h4>
            <table class="table table-responsive table-striped" style="text-align: left">
                $novedadesHTML
            </table>
        </div>
</div>
HTML;
        return $return;
    }

    private function getEpicrisis(?\App\Entity\Cliente $cliente) {
        $epicrisisHTML = '';

        $return = <<<HTML
<div class="row">
        <div class="col-12 historia-al-ingreso" >
            <h4 style="border-top: 1px solid; padding-top: 10px">Epicrisis</h4>
            <table class="table table-responsive table-striped" style="text-align: left">
                $epicrisisHTML
            </table>
        </div>
</div>
HTML;
        return $return;
    }

}