<?php

namespace Boehsermoe\HbConnector\Service;

use DeepL\Language;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HbApiService
{
    const SITES = [
        "agb",
        "widerruf",
        "widerruf_download",
        "widerruf_reperatur",
        "zahlungundversand",
        "datenschutz",
        "impressum",
        "batterie",
    ];

    public function __construct(
        private readonly ContainerInterface     $container,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $languageRepository,
        private readonly HttpClientInterface    $httpClient,
        private readonly SystemConfigService    $systemConfigService,
        #[Autowire(param: 'monolog.logger')]
        private readonly LoggerInterface $logger,
    )
    {
    }

    private function getResource($resource = null, $options = null, $textmode = 'html')
    {
        if ($resource == "lang") {
            $resource = "https://api.haendlerbund.de/shopware/available_lang.php";
        } else {
            switch (trim($options['did_name'])) {
                case "agb":
                    $did = "12766C46A8A";
                    break;
                case "widerruf":
                    $did = "12766C53647";
                    break;
                case "widerruf_download":
                    $did = "1452C24576D";
                    break;
                case "widerruf_reperatur":
                    $did = "1463C5DBF05";
                    break;
                case "zahlungundversand":
                    $did = "12766C58F26";
                    break;
                case "datenschutz":
                    $did = "12766C5E204";
                    break;
                case "impressum":
                    $did = "1293C20B491";
                    break;
                case "batterie":
                    $did = "134CBB4D101";
                    break;
                default:
                    $did = "";
                    break;
            }

            $lang = $options['lang'] == 'de' ? '' : $options['lang'];
            $textmode = $textmode == 'html' ? '' : "&mode=" . $textmode;

            $resource = "https://www.hb-intern.de/www/hbm/api/live_rechtstexte.htm" .
                "?APIkey=1IqJF0ap6GdDNF7HKzhFyciibdml8t4v" .
                "&did={$did}" .
                "&AccessToken={$options['token']}" .
                "&lang={$lang}{$textmode}";
        }

        return $resource;
    }

    private function getCurlOptions($resource)
    {
        return [
            'base_uri' => $resource,
            'headers' => [
                'User-Agent' => 'INPUT DATA SCRIPT'
            ],
            'timeout' => 50,
            'verify_peer' => false,
            'verify_host' => false,
        ];
    }

    private function sendRequest($params, $data = null)
    {
        $resource = $this->getResource($params['resource'], $params['options'], $params['textmode']);
        $options = $this->getCurlOptions($resource);

        $response = $this->httpClient->request('GET', $resource, $options);

        $status = $response->getStatusCode();
        $content = $response->getContent(false);

        return ['status' => $status, 'data' => $content];
    }

    public function checkLawTexts()
    {
        $results = [];
        foreach ($this->getShops() as $saleChannel) {
            foreach ($saleChannel->getLanguages() as $language) {
                $response = $this->updateSingleLawText($saleChannel->getId(), $language);
                if ($response['success']) {
                    $results['success'][$saleChannel->getName()][$language->getLocale()->getCode()] = $response['success'];
                } elseif ($response['error']) {
                    $results['error'][$saleChannel->getName()][$language->getLocale()->getCode()] = $response['error'];
                } else {
                    $results['error'][$saleChannel->getName()][$language->getLocale()->getCode()] = 'No response';
                }
            }
        }

        return $results;
    }

    private function updateSingleLawText($salesChannelId, LanguageEntity $language)
    {
        $shopconfig = $this->getShopConfig($salesChannelId);
        $shopconfig['lang'] = substr($language->getLocale()->getCode(), 0, 2);

        if ($shopconfig['apiKey'] == "false") {
            $this->writeLog([
                'shop_id' => $salesChannelId,
                'site_id' => null,
                'request' => 'check API Key',
                'response' => 'API key is empty or false',
                'state' => 0
            ]);
            return ['error' => 'API key is empty or false'];
        }

        if (strlen($shopconfig['apiKey']) !== 36) {
            $this->writeLog([
                'shop_id' => $salesChannelId,
                'site_id' => null,
                'request' => 'check API Key',
                'response' => 'API key is not 36 characters long',
                'state' => 0
            ]);
            return ['error' => 'API key is not 36 characters long'];
        }

        $updateStatuses = [];
        foreach (self::SITES as $site) {
            $options = ['site' => $site, 'lang' => $shopconfig['lang'], 'textmode' => $shopconfig['textmode']];
            $response = $this->getRemoteLawTexts($options, trim($shopconfig['apiKey']));

            if ($response['status'] != 200 || $response['data'] == '' || $response['data'] == 'SHOP_NOT_FOUND') {
                $this->writeLog([
                    'shop_id' => $salesChannelId,
                    'site_id' => $site,
                    'request' => 'Could not update page with site_id: ' . $site,
//                    'response' => $response['data'],
                    'state' => 0
                ]);
                $updateStatuses[$site] = $response;
            } else {
                $this->updateSystemConfig($site, $response['data'], $salesChannelId, $shopconfig['lang']);
//                $updateStatus = $this->updateCmsSite($site, $response, $salesChannelId, $language);
                $updateStatuses[$site] = 'success';
            }
        }

        return ['success' => $updateStatuses];
    }

    private function getShops()
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation('domains');
        $criteria
            ->addAssociation('currency')
            ->addAssociation('languages')
            ->addAssociation('languages.locale');
        /** @var SalesChannelEntity[] $saleChannels */
        $saleChannels = $this->salesChannelRepository->search($criteria, Context::createDefaultContext());

        return $saleChannels;
    }

    private function getShopConfig($salesChannelId)
    {
        $apiKey = $this->systemConfigService->get('BoehsermoeHbConnector.config.apiKey', $salesChannelId);
        $textmode = $this->systemConfigService->get('BoehsermoeHbConnector.config.textmode', $salesChannelId);

        return [
            'apiKey' => $apiKey,
            'shopId' => $salesChannelId,
            'version' => $this->container->getParameter('kernel.shopware_version'),
            'textmode' => $textmode,
        ];
    }

    private function getRemoteLawTexts($options, $apikey)
    {
        return $this->sendRequest([
            'resource' => 'texts',
            'textmode' => $options['textmode'],
            'options' => [
                'did_name' => $options['site'],
                'token' => $apikey,
                'lang' => $options['lang'],
            ]
        ]);
    }

    private function writeLog(array $params)
    {
        $this->logger->info(json_encode($params));
    }

    /**
     * @param $site
     * @param $html
     * @param $salesChannelId
     * @return false|\Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent
     */
    private function updateCmsSite($site, $html, $salesChannelId, LanguageEntity $language)
    {
        switch ($site) {
            case 'agb':
                $siteConfigName = "core.basicInformation.tosPage";
                break;
            default:
                return false;
        }

        $siteId = $this->systemConfigService->get($siteConfigName, $salesChannelId);

        /** @var EntityRepository $cmsRepository */
        $cmsRepository = $this->container->get('cms_page.repository');
        $criteria = new Criteria([$siteId]);
        $criteria->addAssociation('sections.blocks.slots');
        /** @var \Shopware\Core\Content\Cms\CmsPageEntity $cmsPage */
        $cmsPage = $cmsRepository->search($criteria, Context::createDefaultContext())->first();

        if ($cmsPage) {
            $slot = $cmsPage->getSections()->first()->getBlocks()->first()->getSlots()->first();
            $slotConfig = $slot->getConfig();
            $slotConfig['content']['value'] = $html['data'];

            /** @var EntityRepository $slotTranslationRepository */
            $slotTranslationRepository = $this->container->get('cms_slot_translation.repository');
            $slotRepository = $this->container->get('cms_slot.repository');
            $event = $slotRepository->update([
                [
                    'id' => $slot->getId(),
                    'translations' => [
                        $language->getId() => [
                            'config' => $slotConfig,
                        ]
                    ]
                ]
            ], Context::createDefaultContext());

            $this->writeLog([
                'shop_id' => $salesChannelId,
                'site_id' => $site,
                'language' => $language->getLocale()->getCode(),
                'request' => 'update page with site_id: ' . $site . ' with new lawtext',
//                                            'response' => $remote_lawtext['data'],
                'event' => $event->getList(),
                'state' => 1
            ]);

            return $event;
        }

        return false;
    }

    protected function updateSystemConfig(string $site, string $remote_lawtext, $salesChannelId, string $langCode)
    {
        $this->systemConfigService->set("BoehsermoeHbConnector.config.lawtext.$site.$langCode", $remote_lawtext, $salesChannelId);
        $this->writeLog([
            'shop_id' => $salesChannelId,
            'site_id' => $site,
            'language' => $langCode,
            'request' => 'update system config with site_id: ' . $site . ' with new lawtext',
//                                            'response' => $remote_lawtext['data'],
            'event' => 'updateSystemConfig',
            'state' => 1
        ]);
    }

    protected function getSystemConfig(string $site, string $langCode, ?string $salesChannelId = null) : ?string
    {
        return $this->systemConfigService->getString("BoehsermoeHbConnector.config.lawtext.$site.$langCode", $salesChannelId);
    }

    public function getLawText(string $site, SalesChannelContext $context)
    {
        $criteria = new Criteria([$context->getLanguageId()]);
        $criteria->addAssociation('locale');

        $searchResult = $this->languageRepository->search($criteria, $context->getContext());

        /** @var LanguageEntity $language */
        $language = $searchResult->first();
        $langCode = substr($language->getLocale()->getCode(), 0, 2);

        $systemConfig = $this->getSystemConfig($site, $langCode, $context->getSalesChannelId());
        return $systemConfig;
    }
}
