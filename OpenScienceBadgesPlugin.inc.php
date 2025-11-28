<?php

use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;

import('lib.pkp.classes.plugins.GenericPlugin');

class OpenScienceBadgesPlugin extends GenericPlugin
{
    public const BADGE_DATA = 'data';
    public const BADGE_MATERIALS = 'materials';
    public const BADGE_PREREGISTERED = 'preregistered';
    public const BADGE_PREREGISTERED_PLUS = 'preregisteredplus';

    public const BADGES = [
        self::BADGE_DATA,
        self::BADGE_MATERIALS,
        self::BADGE_PREREGISTERED,
        self::BADGE_PREREGISTERED_PLUS,
    ];

    public const SIZE_LARGE = 'large';
    public const SIZE_SMALL = 'small';

    public const COLOR_COLOR = 'color';
    public const COLOR_GRAY = 'gray';

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
        HookRegistry::register('Schema::get::publication', [$this, 'addToPublicationSchema']);
        HookRegistry::register('Form::config::before', [$this, 'addToPublicationMetadataForm']);
        HookRegistry::register('Templates::Article::Details', [$this, 'addToArticle']);
        HookRegistry::register('Templates::Article::Main', [$this, 'addToArticle']);

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
        if (!defined('FORM_METADATA') || $form->id !== FORM_METADATA) {
            return false;
        }

        $context = Application::get()->getRequest()->getContext();
        if (!$context) {
            return false;
        }

        $submission = Application::get()->getRequest()->getRouter()->getHandler()->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
        if (!$submission) {
            return false;
        }


        foreach (self::BADGES as $badge) {
            $form->addField(
                new FieldText($this->getPropName($badge), [
                    'label' => $badge,
                    'isMultilingual' => true,
                    'value' => $submission->getCurrentPublication()->getData($this->getPropName($badge)),
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
        $publication = $templateMgr->get_template_vars('publication');

        if (!$this->publicationHasBadges($publication)) {
            return false;
        }

        $templateMgr->assign([
            'osbBadgesDisplay' => $this->getLargeBadgesHTML($publication, $templateMgr),
        ]);

        $output .= $templateMgr->fetch($this->getTemplateResource('article-details.tpl'));

        return false;
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
        $badges = [];
        foreach (self::BADGES as $badge) {
            $badges[] = [
                'name' => __("plugins.generic.openScienceBadges.badgeTitleFormat", ['title' => __("plugins.generic.openScienceBadges.{$badge}")]),
                'alt' => __("plugins.generic.openScienceBadges.{$badge}.alt"),
                'desc' => $publication->getLocalizedData($this->getPropName($badge)),
                'url' => $this->getPluginUrl() . "/images/{$badge}_{$size}_color.png",
            ];
        }
        return $badges;
    }

    /**
     * Get the URL to the plugin's root directory
     */
    protected function getPluginUrl(): string
    {
        $request = Application::get()->getRequest();
        $baseUrl = rtrim($request->getBaseUrl(), '/');
        $pluginPath = rtrim($this->getPluginPath(), '/');
        return "{$baseUrl}/{$pluginPath}";
    }
}
