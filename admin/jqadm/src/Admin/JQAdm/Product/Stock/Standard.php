<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2016
 * @package Admin
 * @subpackage JQAdm
 */


namespace Aimeos\Admin\JQAdm\Product\Stock;


/**
 * Default implementation of product stock JQAdm client.
 *
 * @package Admin
 * @subpackage JQAdm
 */
class Standard
	extends \Aimeos\Admin\JQAdm\Common\Admin\Factory\Base
	implements \Aimeos\Admin\JQAdm\Common\Admin\Factory\Iface
{
	/** admin/jqadm/product/stock/standard/subparts
	 * List of JQAdm sub-clients rendered within the product stock section
	 *
	 * The output of the frontend is composed of the code generated by the JQAdm
	 * clients. Each JQAdm client can consist of serveral (or none) sub-clients
	 * that are responsible for rendering certain sub-parts of the output. The
	 * sub-clients can contain JQAdm clients themselves and therefore a
	 * hierarchical tree of JQAdm clients is composed. Each JQAdm client creates
	 * the output that is placed inside the container of its parent.
	 *
	 * At first, always the JQAdm code generated by the parent is printed, then
	 * the JQAdm code of its sub-clients. The order of the JQAdm sub-clients
	 * determines the order of the output of these sub-clients inside the parent
	 * container. If the configured list of clients is
	 *
	 *  array( "subclient1", "subclient2" )
	 *
	 * you can easily change the order of the output by reordering the subparts:
	 *
	 *  admin/jqadm/<clients>/subparts = array( "subclient1", "subclient2" )
	 *
	 * You can also remove one or more parts if they shouldn't be rendered:
	 *
	 *  admin/jqadm/<clients>/subparts = array( "subclient1" )
	 *
	 * As the clients only generates structural JQAdm, the layout defined via CSS
	 * should support adding, removing or reordering content by a fluid like
	 * design.
	 *
	 * @param array List of sub-client names
	 * @since 2016.01
	 * @category Developer
	 */
	private $subPartPath = 'admin/jqadm/product/stock/standard/subparts';
	private $subPartNames = array();


	/**
	 * Copies a resource
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function copy()
	{
		$view = $this->getView();

		$this->setData( $view );
		$view->stockBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->stockBody .= $client->copy();
		}

		/** admin/jqadm/product/stock/template-item
		 * Relative path to the HTML body template of the stock subpart for products.
		 *
		 * The template file contains the HTML code and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in admin/jqadm/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "default" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "default"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating the HTML code
		 * @since 2016.04
		 * @category Developer
		 */
		$tplconf = 'admin/jqadm/product/stock/template-item';
		$default = 'product/item-stock-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Creates a new resource
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function create()
	{
		$view = $this->getView();

		$this->setData( $view );
		$view->stockBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->stockBody .= $client->create();
		}

		$tplconf = 'admin/jqadm/product/stock/template-item';
		$default = 'product/item-stock-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Returns a single resource
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function get()
	{
		$view = $this->getView();

		$this->setData( $view );
		$view->stockBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->stockBody .= $client->get();
		}

		$tplconf = 'admin/jqadm/product/stock/template-item';
		$default = 'product/item-stock-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Saves the data
	 *
	 * @return string|null admin output to display or null for redirecting to the list
	 */
	public function save()
	{
		$view = $this->getView();
		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'stock' );
		$manager->begin();

		try
		{
			$this->updateItems( $view );
			$view->stockBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->stockBody .= $client->save();
			}

			$manager->commit();
			return;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item-stock' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', array() ) + $error;
			$manager->rollback();
		}
		catch( \Exception $e )
		{
			$context->getLogger()->log( $e->getMessage() . ' - ' . $e->getTraceAsString() );
			$error = array( 'product-item-stock' => $e->getMessage() );
			$view->errors = $view->get( 'errors', array() ) + $error;
			$manager->rollback();
		}

		throw new \Aimeos\Admin\JQAdm\Exception();
	}


	/**
	 * Returns the sub-client given by its name.
	 *
	 * @param string $type Name of the client type
	 * @param string|null $name Name of the sub-client (Default if null)
	 * @return \Aimeos\Admin\JQAdm\Iface Sub-client object
	 */
	public function getSubClient( $type, $name = null )
	{
		/** admin/jqadm/product/stock/decorators/excludes
		 * Excludes decorators added by the "common" option from the product JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "admin/jqadm/common/decorators/default" before they are wrapped
		 * around the JQAdm client.
		 *
		 *  admin/jqadm/product/stock/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Admin\JQAdm\Common\Decorator\*") added via
		 * "admin/jqadm/common/decorators/default" to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/stock/decorators/global
		 * @see admin/jqadm/product/stock/decorators/local
		 */

		/** admin/jqadm/product/stock/decorators/global
		 * Adds a list of globally available decorators only to the product JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("\Aimeos\Admin\JQAdm\Common\Decorator\*") around the JQAdm client.
		 *
		 *  admin/jqadm/product/stock/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Admin\JQAdm\Common\Decorator\Decorator1" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/stock/decorators/excludes
		 * @see admin/jqadm/product/stock/decorators/local
		 */

		/** admin/jqadm/product/stock/decorators/local
		 * Adds a list of local decorators only to the product JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("\Aimeos\Admin\JQAdm\Product\Decorator\*") around the JQAdm client.
		 *
		 *  admin/jqadm/product/stock/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Admin\JQAdm\Product\Decorator\Decorator2" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/stock/decorators/excludes
		 * @see admin/jqadm/product/stock/decorators/global
		 */
		return $this->createSubClient( 'product/stock/' . $type, $name );
	}


	/**
	 * Returns the list of sub-client names configured for the client.
	 *
	 * @return array List of JQAdm client names
	 */
	protected function getSubClientNames()
	{
		return $this->getContext()->getConfig()->get( $this->subPartPath, $this->subPartNames );
	}


	/**
	 * Returns the mapped input parameter or the existing items as expected by the template
	 *
	 * @param \Aimeos\MW\View\Iface $view View object with helpers and assigned parameters
	 */
	protected function setData( \Aimeos\MW\View\Iface $view )
	{
		$typeManager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'stock/type' );

		$view->stockTypes = $typeManager->searchItems( $typeManager->createSearch() );
		$view->stockData = (array) $view->param( 'stock', array() );

		if( !empty( $view->stockData ) || ( $code = $view->item->getCode() ) == '' ) {
			return;
		}

		$data = array();
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'stock' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'stock.productcode', $code ) );
		$search->setSortations( array( $search->sort( '+', 'stock.type.code' ) ) );

		foreach( $manager->searchItems( $search ) as $item )
		{
			foreach( $item->toArray() as $key => $value ) {
				$data[$key][] = $value;
			}
		}

		$view->stockData = $data;
	}


	/**
	 * Updates existing stock items or creates new ones
	 *
	 * @param \Aimeos\MW\View\Iface $view View object with helpers and assigned parameters
	 */
	protected function updateItems( \Aimeos\MW\View\Iface $view )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'stock' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'stock.productcode', $view->item->getCode() ) );
		$items = $manager->searchitems( $search );

		$list = (array) $view->param( 'stock/stock.id', array() );

		foreach( $list as $idx => $id )
		{
			if( !isset( $items[$id] ) ) {
				$item = $manager->createItem();
			} else {
				$item = $items[$id];
			}

			$item->setProductCode( $view->item->getCode() );
			$item->setTypeId( $view->param( 'stock/stock.typeid/' . $idx ) );
			$item->setStocklevel( $view->param( 'stock/stock.stocklevel/' . $idx ) );
			$item->setDateBack( $view->param( 'stock/stock.dateback/' . $idx ) );

			$manager->saveItem( $item, false );
		}

		$manager->deleteItems( array_diff( array_keys( $items ), $list ) );
	}
}
