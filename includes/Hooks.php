<?php

namespace AdvancedSearch;

use BetaFeatures;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use ResourceLoader;
use SpecialPage;
use User;

class Hooks {

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialPageBeforeExecute
	 *
	 * @param SpecialPage $special
	 * @param string $subpage
	 * @return boolean
	 */
	public static function onSpecialPageBeforeExecute( SpecialPage $special, $subpage ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		/**
		 * If the BetaFeatures extension is loaded then require the current user
		 * to have the feature enabled.
		 */
		if (
			$config->get( 'AdvancedSearchBetaFeature' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' ) &&
			!BetaFeatures::isFeatureEnabled( $special->getUser(), 'advancedsearch' )
		) {
			return;
		}
		if ( $special->getName() === 'Search' ) {
			$special->getOutput()->addModules( 'ext.advancedSearch.init' );
			$special->getOutput()->addModuleStyles( 'ext.advancedSearch.initialstyles' );

			$special->getOutput()->addJsConfigVars(
				'advancedSearch.mimeTypes',
				( new MimeTypeConfigurator( MediaWikiServices::getInstance()->getMimeAnalyzer() ) )
					->getMimeTypes( $special->getConfig()->get( 'FileExtensions' ) )
			);
			$special->getOutput()->addJsConfigVars(
				'advancedSearch.tooltips', TooltipGenerator::generateToolTips()
			);
		}
	}

	/**
	 * @param User $user
	 * @param array[] &$prefs
	 */
	public static function getBetaFeaturePreferences( User $user, array &$prefs ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		/**
		 * If the BetaFeatures extension is loaded then require the current user
		 * to have the feature enabled.
		 */
		if ( !$config->get( 'AdvancedSearchBetaFeature' ) ) {
			return;
		}

		$extensionAssetsPath = $config->get( 'ExtensionAssetsPath' );

		$prefs['advancedsearch'] = [
			'label-message' => 'advancedSearch-beta-feature-message',
			'desc-message' => 'advancedSearch-beta-feature-description',
			'screenshot' => [
				'ltr' => "$extensionAssetsPath/AdvancedSearch/resources/AdvancedSearch-beta-features-ltr.svg",
				'rtl' => "$extensionAssetsPath/AdvancedSearch/resources/AdvancedSearch-beta-features-rtl.svg",
			],
			'info-link' => 'https://www.mediawiki.org/wiki/Help:Extension:AdvancedSearch',
			'discussion-link' => 'https://www.mediawiki.org/wiki/Help_talk:Extension:AdvancedSearch',
		];
	}

	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader $rl ) {
		$testModules['qunit']['ext.advancedSearch.tests'] = [
			'scripts' => [
				'tests/qunit/ui/ArbitraryWordInput.test.js',
				'tests/qunit/ui/NamespaceFilters.test.js',
				'tests/qunit/ui/NamespacePresets.test.js',
				'tests/qunit/ui/SearchPreview.test.js',
				'tests/qunit/ui/TemplateSearch.test.js',
				'tests/qunit/dm/SearchModel.test.js',
				'tests/qunit/dm/FileTypeOptionProvider.test.js',
				'tests/qunit/dm/trackingEvents/SearchRequest.test.js'
			],
			'dependencies' => [
				'ext.advancedSearch.ui.ArbitraryWordInput',
				'ext.advancedSearch.ui.NamespaceFilters',
				'ext.advancedSearch.ui.NamespacePresets',
				'ext.advancedSearch.ui.SearchPreview',
				'ext.advancedSearch.ui.TemplateSearch',
				'ext.advancedSearch.dm.SearchModel',
				'ext.advancedSearch.dm.FileTypeOptionProvider',
				'ext.advancedSearch.dm.trackingEvents.SearchRequest',
				'oojs-ui'
			],
			'localBasePath' => dirname( __DIR__ ),
			'remoteExtPath' => 'AdvancedSearch',
		];
	}
}