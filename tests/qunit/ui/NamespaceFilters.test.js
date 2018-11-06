( function ( mw ) {
	var NamespaceFilters = mw.libs.advancedSearch.ui.NamespaceFilters,
		Model = mw.libs.advancedSearch.dm.SearchModel;

	QUnit.module( 'ext.advancedSearch.ui.NamespaceFilters' );

	QUnit.assert.namespaceElementsPresent = function ( element, expectedNamespaces, message ) {
		var actualNamespaces = [];
		element.find( 'input' ).each( function () {
			actualNamespaces.push( $( this ).prop( 'name' ).replace( /^ns/, '' ) );
		} );
		this.deepEqual( actualNamespaces, expectedNamespaces, message );
	};

	QUnit.test( 'StoreUpdate event handler updates hidden namespace fields', function ( assert ) {
		var model = new Model(),
			filter = new NamespaceFilters( model, {
				namespaces: {
					0: 'Article',
					1: 'Talk',
					2: 'User',
					3: 'UserTalk'
				}
			} );

		assert.namespaceElementsPresent( filter.$namespaceContainer, [], 'There is no hardcoded namespace preset' );
		model.setNamespaces( [ '1', '3' ] );
		assert.namespaceElementsPresent( filter.$namespaceContainer, [ '1', '3' ] );
	} );

	QUnit.test( 'Value update propagates to model', function ( assert ) {
		var model = new Model(),
			filter = new NamespaceFilters( model, {
				namespaces: {
					0: 'Article',
					1: 'Talk',
					2: 'User',
					3: 'UserTalk'
				}
			} );

		model.setNamespaces = function ( namespaces ) {
			assert.deepEqual( namespaces, [ '1', '2' ] );
		};
		filter.getValue = function () {
			return [ '1', '2' ];
		};

		filter.onValueUpdate();
	} );

	QUnit.test( 'Choosing a namespace from the menu clears the input field', function ( assert ) {
		var model = new Model(),
			filter = new NamespaceFilters( model, {
				namespaces: {
					0: 'Article',
					1: 'Talk',
					2: 'User',
					3: 'UserTalk'
				}
			} );

		filter.input.setValue( 'Use' );
		filter.getMenu().chooseItem( filter.getMenu().getItems()[ 0 ] );
		assert.strictEqual( filter.input.getValue(), '' );
	} );

	QUnit.test( 'On multiple namespaces either one can be removed', function ( assert ) {
		var model = new Model(),
			filter = new NamespaceFilters( model, {
				namespaces: {
					0: 'Article',
					1: 'Talk',
					2: 'User',
					3: 'UserTalk'
				}
			} );

		model.setNamespaces( [ '1', '2', '3' ] );
		assert.notOk( filter.getMenu().getItemFromLabel( 'Article' ).isSelected() );
		assert.ok( filter.getMenu().getItemFromLabel( 'Talk' ).isSelected() );
		assert.ok( filter.getMenu().getItemFromLabel( 'User' ).isSelected() );
		assert.ok( filter.getMenu().getItemFromLabel( 'UserTalk' ).isSelected() );
	} );

}( mediaWiki ) );
