<?php
/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Job\Controller;

use Gally\Job\Entity\Job;
use Gally\Job\Service\JobManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DownloadJobFile extends AbstractController
{
    public function __construct(
        private JobManager $jobManager
    ) {
    }

    public function __invoke(Job $job, Request $request): Response
    {
        $filePath = $this->jobManager->getAbsoluteJobFilePath($job);
        if (!$filePath || !file_exists($filePath)) {
            return new Response('File not found', 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $job->getFile()->filePath
        );

        return $response;
    }
}
