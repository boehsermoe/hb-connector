<?php declare(strict_types=1);

namespace Boehsermoe\HbConnector\DataResolver;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\SalesChannel\Struct\TextStruct;
use Boehsermoe\HbConnector\Service\HbApiService;

class HbLawtextCmsElementResolver extends AbstractCmsElementResolver
{
    public function __construct(public readonly HbApiService $hbApiService)
    {
    }

    public function getType(): string
    {
        return 'hb-lawtext-site';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        /** @var FieldConfig $siteId */
        $siteId = $config->get('siteId');
        $siteId->getIntValue();

        $response = $this->hbApiService->getLawText($siteId->getStringValue(), $resolverContext->getSalesChannelContext());
        if ($response) {
            $text = new TextStruct();
            $text->setContent($response);
            $slot->setData($text);
        }

        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {

    }
}