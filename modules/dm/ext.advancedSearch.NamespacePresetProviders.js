( function ( mw, $ ) {
	'use strict';

	mw.libs = mw.libs || {};
	mw.libs.advancedSearch = mw.libs.advancedSearch || {};
	mw.libs.advancedSearch.dm = mw.libs.advancedSearch.dm || {};

	/**
	 * Fired when the namespace ID providers are initialized
	 *
	 * The real event name is `advancedSearch.initNamespacePresetProviders`, but jsDuck does not support dots in event names.
	 *
	 * @event advancedSearch_initNamespacePresetProviders
	 * @param {object} providerFunctions
	 */

	/**
	 * @param {ext.advancedSearch.dm.SearchableNamespaces} namespaces
	 * @constructor
	 */
	mw.libs.advancedSearch.dm.NamespacePresetProviders = function ( namespaces ) {
		this.namespaces = namespaces;
		this.providerFunctions = {
			all: function ( namespaceIds ) {
				return namespaceIds;
			},
			discussion: function ( namespaceIds ) {
				return $.grep( namespaceIds, function ( id ) {
					return Number( id ) % 2;
				} );
			},
			defaultNamespaces: function () {
				return mw.libs.advancedSearch.dm.getDefaultNamespaces( mw.user.options.values );
			}
		};
		mw.hook( 'advancedSearch.initNamespacePresetProviders' ).fire( this.providerFunctions );
	};

	OO.initClass( mw.libs.advancedSearch.dm.NamespacePresetProviders );

	mw.libs.advancedSearch.dm.NamespacePresetProviders.prototype.hasProvider = function ( providerName ) {
		return this.providerFunctions.hasOwnProperty( providerName );
	};

	/**
	 * @param {String} providerName
	 * @return {String[]}
	 */
	mw.libs.advancedSearch.dm.NamespacePresetProviders.prototype.getNamespaceIdsFromProvider = function ( providerName ) {
		var self = this;

		return this.providerFunctions[ providerName ]( this.namespaces.getNamespaceIds() )
			// Calling String() as a function casts numbers to strings
			.map( String )
			.filter( function ( id ) {
				if ( id in self.namespaces.getNamespaces() ) {
					return true;
				}
				mw.log.warn( 'AdvancedSearch namespace preset provider "' + providerName + '" returned invalid namespace ID' );
				return false;
			} );
	};

	/**
	 * @param {String[]} namespaceIds
	 * @return {bool}
	 */
	mw.libs.advancedSearch.dm.NamespacePresetProviders.prototype.namespaceIdsAreValid = function ( namespaceIds ) {
		return mw.libs.advancedSearch.util.arrayContains( this.namespaces.getNamespaceIds(), namespaceIds );
	};

}( mediaWiki, jQuery ) );
