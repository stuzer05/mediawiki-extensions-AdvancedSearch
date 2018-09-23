( function ( mw ) {
	var CheckboxInputWidget = mw.libs.advancedSearch.ui.CheckboxInputWidget;

	QUnit.module( 'ext.advancedSearch.ui.CheckboxInputWidget' );

	QUnit.test( 'Checkbox does not respond to click events', function ( assert ) {
		var checkbox = new CheckboxInputWidget( {} );
		checkbox.$element.click();
		assert.notOk( checkbox.isSelected() );
	} );

}( mediaWiki ) );