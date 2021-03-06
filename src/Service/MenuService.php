<?php

/*
 * This file is part of aakb/jira_economics.
 *
 * (c) 2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class MenuService
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;

    /** @var \Symfony\Component\Routing\RouterInterface */
    protected $router;

    /** @var \App\Service\ContextService */
    protected $contextService;

    /** @var \App\Service\AppService */
    protected $appService;

    /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface */
    protected $parameters;

    /**
     * MenuService constructor.
     */
    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        ContextService $contextService,
        AppService $appService,
        ParameterBagInterface $parameters
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->contextService = $contextService;
        $this->appService = $appService;
        $this->parameters = $parameters;
    }

    public function getMenuItems()
    {
        switch ($this->contextService->getContext()) {
            case ContextService::JIRA:
                return $this->getMenuItemsInContext('jira');
        }

        return $this->getMenuItemsInContext('portal');
    }

    private function getMenuItemsInContext($context)
    {
        if (!$this->parameters->has($context)) {
            return [];
        }

        $items = $this->parameters->get($context)['menu'] ?? [];
        $items = array_filter($items, [$this->contextService, 'isAccessible']);
        foreach ($items as &$item) {
            $item['active'] = $this->contextService->isActiveRoute($item['routeName']);
        }
        $apps = $this->appService->getApps();
        $items = array_filter($items, static function ($app) use ($apps) {
            return
                // Some menu items are not apps and are access controlled by
                // roles.
                \in_array($app, ['admin', 'jira', 'portal'], true)
                // Check access to app.
                || \array_key_exists($app, $apps);
        }, \ARRAY_FILTER_USE_KEY);

        return $items;
    }

    /**
     * Get global menu items.
     *
     * @return array
     */
    public function getGlobalMenuItems()
    {
        $items = $this->getMenuItems();

        return $items;
    }
}
