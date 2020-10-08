<?php

namespace AdvancedSearch;

use Language;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use User;
use WebRequest;

/**
 * @license GPL-2.0-or-later
 */
class Hooks {

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialPageBeforeExecute
	 *
	 * @param SpecialPage $special
	 * @param string $subpage
	 */
	public static function onSpecialPageBeforeExecute( SpecialPage $special, $subpage ) {
		$services = MediaWikiServices::getInstance();
		$mainConfig = $special->getConfig();

		if ( $special->getName() !== 'Search' ) {
			return;
		}

		/**
		 * If the user is logged in and has explicitly requested to disable the extension, don't load.
		 * Ensure namespaces are always part of search URLs
		 */
		if ( !$special->getUser()->isAnon() &&
			$special->getUser()->getBoolOption( 'advancedsearch-disable' )
		) {
			return;
		}

		/**
		 * Ensure the current URL is specifying the namespaces which are to be used
		 */
		self::redirectToNamespacedRequest( $special );
		if ( $special->getOutput()->getRedirect() ) {
			return;
		}

		$special->getOutput()->addModules( [
			'ext.advancedSearch.init',
			'ext.advancedSearch.searchtoken',
		] );

		$special->getOutput()->addModuleStyles( 'ext.advancedSearch.initialstyles' );

		$special->getOutput()->addJsConfigVars( [
			'advancedSearch.mimeTypes' =>
				( new MimeTypeConfigurator( $services->getMimeAnalyzer() ) )->getMimeTypes(
					$mainConfig->get( 'FileExtensions' )
				),
			'advancedSearch.tooltips' => ( new TooltipGenerator( $special->getContext() ) )->generateTooltips(),
			'advancedSearch.namespacePresets' => $mainConfig->get( 'AdvancedSearchNamespacePresets' ),
			'advancedSearch.deepcategoryEnabled' => $mainConfig->get( 'AdvancedSearchDeepcatEnabled' ),
			'advancedSearch.searchableNamespaces' =>
				SearchableNamespaceListBuilder::getCuratedNamespaces(
					$services->getSearchEngineConfig()->searchableNamespaces()
				),
		] );

		/**
		 * checks if extension Translate is installed and enabled
		 * https://github.com/wikimedia/mediawiki-extensions-Translate/blob/master/Translate.php#L351
		 * this check is not performed with ExtensionRegistry
		 * because Translate extension does not have extension.json
		 */
		if ( $mainConfig->has( 'EnablePageTranslation' ) &&
			$mainConfig->get( 'EnablePageTranslation' )
		) {
			$special->getOutput()->addJsConfigVars(
				'advancedSearch.languages',
				Language::fetchLanguageNames()
			);
		}
	}

	/**
	 * If the request does not contain any namespaces, redirect to URL with user default namespaces
	 * @param \SpecialPage $special
	 */
	private static function redirectToNamespacedRequest( \SpecialPage $special ) {
		if ( !self::isNamespacedSearch( $special->getRequest() ) ) {
			$namespacedSearchUrl = $special->getRequest()->getFullRequestURL();
			$queryParts = [];
			foreach ( self::getDefaultNamespaces( $special->getUser() ) as $ns ) {
				$queryParts['ns' . $ns] = '1';
			}
			$namespacedSearchUrl = wfAppendQuery( $namespacedSearchUrl, $queryParts );
			$special->getOutput()->redirect( $namespacedSearchUrl );
		}
	}

	/**
	 * Retrieves the default namespaces for the current user
	 *
	 * @param User $user The user to lookup default namespaces for
	 * @return int[] List of namespaces to be searched by default
	 */
	public static function getDefaultNamespaces( User $user ): array {
		$searchConfig = MediaWikiServices::getInstance()->getSearchEngineConfig();
		return $searchConfig->userNamespaces( $user ) ?: $searchConfig->defaultNamespaces();
	}

	/**
	 * Checks if there is a search request, and it already specifies namespaces.
	 * @param WebRequest $request
	 * @return bool
	 */
	private static function isNamespacedSearch( WebRequest $request ) {
		if ( $request->getRawVal( 'search', '' ) === '' ) {
			return true;
		}

		foreach ( $request->getValueNames() as $requestKey ) {
			if ( preg_match( '/^ns\d+$/', $requestKey ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResultsPrepend
	 *
	 * @param \SpecialSearch $specialSearch
	 * @param \OutputPage $output
	 * @param string $term
	 */
	public static function onSpecialSearchResultsPrepend(
		\SpecialSearch $specialSearch,
		\OutputPage $output,
		$term ) {
		$output->addHTML(
			\Html::rawElement(
				'div',
				[ 'class' => 'mw-search-spinner' ],
				\Html::element( 'div', [ 'class' => 'mw-search-spinner-bounce' ] )
			)
		);
	}

	/**
	 * @param User $user
	 * @param array[] &$preferences
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['advancedsearch-disable'] = [
			'type' => 'toggle',
			'label-message' => 'advancedsearch-preference-disable',
			'section' => 'searchoptions/advancedsearch',
			'default' => $user->getBoolOption( 'advancedsearch-disable' ),
			'help-message' => 'advancedsearch-preference-help',
		];
	}
}
