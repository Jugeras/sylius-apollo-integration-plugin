<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\Controller;

use PrintPlius\SyliusApolloIntegrationPlugin\Service\ApolloService;
use PrintPlius\SyliusApolloIntegrationPlugin\Form\SettingsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingsController extends AbstractController
{
    private ApolloService $apolloService;

    public function __construct(ApolloService $apolloService)
    {
        $this->apolloService = $apolloService;
    }

    public function indexAction(Request $request): Response
    {
        $form = $this->createForm(SettingsType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->apolloService->validateFile($form)) {
            $excelDirPath = $this->getParameter('kernel.project_dir') . '/var/uploads/apollo';
            $imageDirPath = $this->getParameter('kernel.project_dir') . '/var/uploads/apollo/images';


            /** @var UploadedFile $file */
            $excel = $form->get('excel')->getData();
            $excel->move($excelDirPath, 'apollo.xls');

            $images = $form->get('images')->getData();
            $images->move($imageDirPath, 'images.zip');

            $zip = new \ZipArchive();
            $res = $zip->open($imageDirPath . '/images.zip');
            if ($res === TRUE) {
                $zip->extractTo($imageDirPath);
                $zip->close();
            }

            $this->addFlash('success', 'File uploaded successfully!');
        }

        return $this->render(
            '@PrintPliusSyliusApolloIntegrationPlugin/Settings/index.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
