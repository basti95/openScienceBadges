<?php
namespace APP\plugins\generic\openScienceBadges;

use APP\core\Application;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\publication\Publication;
use APP\template\TemplateManager;
use PKP\components\forms\FieldRichText;
use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;
use PKP\components\forms\publication\PKPMetadataForm;
use PKP\config\Config;
use PKP\context\Context;
use PKP\core\JSONMessage;
use PKP\handler\APIHandler;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class OpenScienceBadgesPlugin extends GenericPlugin
{
    public const BADGE_DATA = 'data';
    public const BADGE_MATERIALS = 'materials';
    public const BADGE_PREREGISTERED = 'preregistered';
    public const BADGE_PREREGISTERED_PLUS = 'preregisteredplus';
    public const SIZE_LARGE = 'large';
    public const SIZE_SMALL = 'small';
    public const COLOR_COLOR = 'color';
    public const COLOR_GRAY = 'gray';
    public const LOCATION_NONE = 'none';
    public const LOCATION_DETAILS = 'details';
    public const LOCATION_MAIN = 'main';
    public const SETTING_SIZE = 'size';
    public const SETTING_COLOR = 'color';
    public const SETTING_LOCATION = 'location';

    public const DEFAULT_SIZE = self::SIZE_SMALL;
    public const DEFAULT_COLOR = self::COLOR_GRAY;
    public const DEFAULT_LOCATION = self::LOCATION_DETAILS;

    public const BADGES = [
        self::BADGE_DATA,
        self::BADGE_MATERIALS,
        self::BADGE_PREREGISTERED,
        self::BADGE_PREREGISTERED_PLUS,
    ];
    public const SIZES = [
        self::SIZE_LARGE,
        self::SIZE_SMALL,
    ];
    public const COLORS = [
        self::COLOR_COLOR,
        self::COLOR_GRAY,
    ];
    public const LOCATIONS = [
        self::LOCATION_NONE,
        self::LOCATION_DETAILS,
        self::LOCATION_MAIN,
    ];

    public function getDisplayName()
    {
        return __('plugins.generic.openScienceBadges.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.openScienceBadges.description');
    }

    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }
        Hook::add('Schema::get::publication', [$this, 'addToPublicationSchema']);
        Hook::add('Form::config::before', [$this, 'addToPublicationMetadataForm']);
        Hook::add('Templates::Article::Details', [$this, 'addToArticle']);
        Hook::add('Templates::Article::Main', [$this, 'addToArticle']);

        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $templateMgr->addStyleSheet(
            'opensciencebadges',
            $this->getPluginUrl() . '/styles/badges.css',
            [
                'context' => ['frontend-article'],
            ]
        );

        return true;
    }

    /**
     * Handle requests for the settings form
     */
    public function manage($args, $request)
    {
        $settingsForm = new OpenScienceBadgesSettingsForm($this);
        switch($request->getUserVar('verb')) {
            case 'settings':
                $settingsForm->initData();
                return new JSONMessage(true, $settingsForm->fetch($request));
            case 'save':
                $settingsForm->readInputData();
                if ($settingsForm->validate()) {
                    $settingsForm->execute();
                    $notificationManager = new NotificationManager();
                    $notificationManager->createTrivialNotification(
                        $request->getUser()->getId(),
                        Notification::NOTIFICATION_TYPE_SUCCESS,
                        array('contents' => __('plugins.generic.openScienceBadges.settings.saved'))
                    );
                    return new JSONMessage(true);
                }
                return new JSONMessage(true, $settingsForm->fetch($request));
        }
        return parent::manage($args, $request);
    }

    /**
     * Add settings link to plugin table
     */
    public function getActions($request, $verb)
    {
        if (!$this->getEnabled()) {
            return parent::getActions($request, $verb);
        }

        return array_merge([
            new LinkAction(
                'settings',
                new AjaxModal(
                    $request->getRouter()->url(
                        $request,
                        null,
                        null,
                        'manage',
                        null,
                        [
                            'verb' => 'settings',
                            'plugin' => $this->getName(),
                            'category' => 'generic',
                        ]
                    ),
                    $this->getDisplayName()
                ),
                __('manager.plugins.settings'),
                null
            ),
        ], parent::getActions($request, $verb));
    }


    /**
     * Extend the publication entity schema to add properties for
     * the open science badges
     */
    public function addToPublicationSchema(string $hookName, array $args): bool
    {
        $schema = $args[0];

        foreach (self::BADGES as $badge) {
            $propName = $this->getPropName($badge);
            $schema->properties->{$propName} = (object) [
                'type' => 'string',
                'multilingual' => true,
                'validation' => ['nullable']
            ];
        }

        return false;
    }

    /**
     * Extend the publication metadata form to add fields for the
     * open science badges
     */
    public function addToPublicationMetadataForm(string $hookName, FormComponent $form): bool
    {
        if ($form->id !== PKPMetadataForm::FORM_METADATA) {
            return false;
        }
        /** @var PKPMetadataForm $form */

        $context = Application::get()->getRequest()->getContext();
        if (!$context) {
            return false;
        }

        $allowedHtml = Config::getVar('security', 'allowed_title_html', 'b,i,u,sup,sub');
        $allowedHtml .= ',a';

        foreach (self::BADGES as $badge) {
            $form->addField(
                new FieldRichText($this->getPropName($badge), [
                    'label' => __("plugins.generic.openScienceBadges.{$badge}"),
                    'tooltip' =>  __(
                        "plugins.generic.openScienceBadges.{$badge}.desc",
                        ['url' => 'https://www.cos.io/initiatives/badges']
                    ),
                    'plugins' => ['link'],
                    'toolbar' => 'bold italic superscript subscript link',
                    'validElements' => $allowedHtml,
                    'isMultilingual' => true,
                    'value' => $form->publication->getData($this->getPropName($badge)),
                ])
            );
        }

        return false;
    }

    /**
     * Get the publication property name for a badge
     */
    public function getPropName(string $badge): string
    {
        return "openScienceBadges:{$badge}";
    }

    /**
     * Add the open badges to the article details hook
     *
     * This hook is usually used for small metadata content and appears
     * as a sidebar in the default theme.
     */
    public function addToArticle(string $hookName, array $args): bool
    {
        $output =& $args[2];

        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $publication = $templateMgr->getTemplateVars('publication');

        if (!$this->publicationHasBadges($publication)) {
            return false;
        }

        $context = Application::get()->getRequest()->getContext();
        $location = $this->getSetting($context->getId(), self::SETTING_LOCATION);
        if (
            !is_string($location)
            || $location === self::LOCATION_NONE
            || ($location === self::LOCATION_DETAILS && $hookName !== 'Templates::Article::Details')
            || ($location === self::LOCATION_MAIN && $hookName !== 'Templates::Article::Main')
        ) {
            return false;
        }

        $output .= $this->getBadgesHTML($context, $publication, $templateMgr, $location);

        return false;
    }

    /**
     * Get the correct HTML output of the badges based
     * on the settings
     */
    public function getBadgesHTML(Context $context, Publication $publication, TemplateManager $templateMgr, string $location = self::DEFAULT_LOCATION): string
    {
        $size = $this->getSetting($context->getId(), self::SETTING_SIZE);

        $templateMgr->assign([
            'osbBadgesDisplay' => $size === self::SIZE_LARGE
                ? $this->getLargeBadgesHTML($publication, $templateMgr)
                : $this->getSmallBadgesHTML($publication, $templateMgr),
            'location' => $location,
        ]);

        return $templateMgr->fetch($this->getTemplateResource('article-details.tpl'));
    }

    /**
     * Get the HTML display of the badges in small mode
     */
    public function getSmallBadgesHTML(Publication $publication, TemplateManager $templateMgr): string
    {
        $templateMgr->assign([
            'osbBadges' => $this->getBadges($publication, self::SIZE_SMALL),
        ]);

        return $templateMgr->fetch($this->getTemplateResource('badges-sm.tpl'));
    }

    /**
     * Get the HTML display of the badges in large mode
     */
    public function getLargeBadgesHTML(Publication $publication, TemplateManager $templateMgr): string
    {
        $templateMgr->assign([
            'osbBadges' => $this->getBadges($publication, self::SIZE_LARGE),
        ]);

        return $templateMgr->fetch($this->getTemplateResource('badges-lg.tpl'));
    }

    /**
     * Does this publication have one or more badges
     */
    public function publicationHasBadges(Publication $publication): bool
    {
        return $publication->getLocalizedData($this->getPropName(self::BADGE_DATA))
            || $publication->getLocalizedData($this->getPropName(self::BADGE_MATERIALS))
            || $publication->getLocalizedData($this->getPropName(self::BADGE_PREREGISTERED))
            || $publication->getLocalizedData($this->getPropName(self::BADGE_PREREGISTERED_PLUS));
    }

    /**
     * Get an array of badge details for a publication
     */
    public function getBadges(Publication $publication, string $size): array
    {
        $color = $this->getSetting(Application::get()->getRequest()->getContext()->getId(), self::SETTING_COLOR);
        if (!$color) {
            $color = self::DEFAULT_COLOR;
        }

        $badges = [];
        foreach (self::BADGES as $badge) {
            $badges[] = [
                'name' => __('plugins.generic.openScienceBadges.' . $badge),
                'desc' => $publication->getLocalizedData($this->getPropName($badge)) ?? '',
                'url' => $this->getPluginUrl() . "/images/{$badge}_{$size}_{$color}.png",
            ];
        }
        return $badges;
    }

    /**
     * Get the URL to the plugin's root directory
     */
    public function getPluginUrl(): string
    {
        $request = Application::get()->getRequest();
        $baseUrl = rtrim($request->getBaseUrl(), '/');
        $pluginPath = rtrim($this->getPluginPath(), '/');
        return "{$baseUrl}/{$pluginPath}";
    }
}
