<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// TODO: Remove extends from AbstractServerRenderDataProvider after closing ticket BB-2188
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountAddressProvider extends AbstractServerRenderDataProvider
{
    const ACCOUNT_ADDRESS_LIST_ROUTE_NAME = 'orob2b_api_account_frontend_get_account_addresses';
    const ACCOUNT_ADDRESS_CREATE_ROUTE_NAME = 'orob2b_account_frontend_account_address_create';
    const ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME = 'orob2b_account_frontend_account_address_update';

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var FragmentHandler
     */
    protected $fragmentHandler;

    /**
     * @param UrlGeneratorInterface $router
     * @param FragmentHandler $fragmentHandler
     */
    public function __construct(UrlGeneratorInterface $router, FragmentHandler $fragmentHandler)
    {
        $this->router = $router;
        $this->fragmentHandler = $fragmentHandler;
    }

    /**
     * @param Account $entity
     * @return array
     */
    public function getComponentOptions(Account $entity)
    {
        $addressListUrl = $this->router->generate(
            self::ACCOUNT_ADDRESS_LIST_ROUTE_NAME,
            ['entityId' => $entity->getId()]
        );
        $addressCreateUrl = $this->router->generate(
            self::ACCOUNT_ADDRESS_CREATE_ROUTE_NAME,
            ['entityId' => $entity->getId()]
        );

        return [
            'entityId' => $entity->getId(),
            'addressListUrl' => $addressListUrl,
            'addressCreateUrl' => $addressCreateUrl,
            'addressUpdateRouteName' => self::ACCOUNT_ADDRESS_UPDATE_ROUTE_NAME,
            'currentAddresses' => $this->fragmentHandler->render($addressListUrl),
        ];
    }
}
