<?php declare(strict_types=1);

namespace Boehsermoe\HbConnector\Controller;

use Boehsermoe\HbConnector\Service\HbApiService;
use Doctrine\DBAL\Exception;
use Jkweb\Shopware\Plugin\AutoTranslate\Entity\Glossary\GlossaryEntity;
use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function md5;

#[Route(defaults: ['_routeScope' => ['api']])]
class ApiController extends AbstractController
{
    public function __construct(ContainerInterface $container, private readonly HbApiService $hbApiService)
    {
        $this->container = $container;
    }

    #[Route(path: '/api/hblawtext/sync', name: 'api.action.hblawtext.sync', methods: ['GET'])]
    public function sync(Context $context): JsonResponse
    {
        $results = $this->hbApiService->checkLawTexts();

        return new JsonResponse(['status' => isset($results['success']) ? 'success' : 'error', 'results' => $results]);
    }
}
