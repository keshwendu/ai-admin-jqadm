<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2017
 * @package Admin
 * @subpackage JQAdm
 */


namespace Aimeos\Admin\JQAdm\Product\Image;

sprintf( 'image' ); // for translation


/**
 * Default implementation of product image JQAdm client.
 *
 * @package Admin
 * @subpackage JQAdm
 */
class Standard
	extends \Aimeos\Admin\JQAdm\Common\Admin\Factory\Base
	implements \Aimeos\Admin\JQAdm\Common\Admin\Factory\Iface
{
	/** admin/jqadm/product/image/standard/subparts
	 * List of JQAdm sub-clients rendered within the product image section
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
	private $subPartPath = 'admin/jqadm/product/image/standard/subparts';
	private $subPartNames = [];


	/**
	 * Copies a resource
	 *
	 * @return string HTML output
	 */
	public function copy()
	{
		$view = $this->getView();

		$view->imageData = $this->toArray( $view->item, true );
		$view->imageTypes = $this->getMediaTypes();
		$view->imageBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->imageBody .= $client->copy();
		}

		return $this->render( $view );
	}


	/**
	 * Creates a new resource
	 *
	 * @return string HTML output
	 */
	public function create()
	{
		$view = $this->getView();
		$data = $view->param( 'image', [] );
		$siteid = $this->getContext()->getLocale()->getSiteId();

		foreach( $view->value( $data, 'product.lists.id', [] ) as $idx => $value ) {
			$data['product.lists.siteid'][$idx] = $siteid;
		}

		$view->imageData = $data;
		$view->imageTypes = $this->getMediaTypes();
		$view->imageBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->imageBody .= $client->create();
		}

		return $this->render( $view );
	}


	/**
	 * Deletes a resource
	 */
	public function delete()
	{
		parent::delete();
		$this->cleanupItems( $this->getView()->item->getListItems( 'media' ), [] );
	}


	/**
	 * Returns a single resource
	 *
	 * @return string HTML output
	 */
	public function get()
	{
		$view = $this->getView();

		$view->imageData = $this->toArray( $view->item );
		$view->imageTypes = $this->getMediaTypes();
		$view->imageBody = '';

		foreach( $this->getSubClients() as $client ) {
			$view->imageBody .= $client->get();
		}

		return $this->render( $view );
	}


	/**
	 * Saves the data
	 */
	public function save()
	{
		$view = $this->getView();
		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'product/lists' );
		$mediaManager = \Aimeos\MShop\Factory::createManager( $context, 'media' );

		$manager->begin();
		$mediaManager->begin();

		try
		{
			$this->fromArray( $view->item, $view->param( 'image', [] ) );
			$view->imageBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->imageBody .= $client->save();
			}

			$mediaManager->commit();
			$manager->commit();
			return;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'product-item-image' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'product-item-image' => $e->getMessage() . ', ' . $e->getFile() . ':' . $e->getLine() );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}

		$mediaManager->rollback();
		$manager->rollback();

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
		/** admin/jqadm/product/image/decorators/excludes
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
		 *  admin/jqadm/product/image/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Admin\JQAdm\Common\Decorator\*") added via
		 * "admin/jqadm/common/decorators/default" to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/image/decorators/global
		 * @see admin/jqadm/product/image/decorators/local
		 */

		/** admin/jqadm/product/image/decorators/global
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
		 *  admin/jqadm/product/image/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Admin\JQAdm\Common\Decorator\Decorator1" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/image/decorators/excludes
		 * @see admin/jqadm/product/image/decorators/local
		 */

		/** admin/jqadm/product/image/decorators/local
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
		 *  admin/jqadm/product/image/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Admin\JQAdm\Product\Decorator\Decorator2" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/product/image/decorators/excludes
		 * @see admin/jqadm/product/image/decorators/global
		 */
		return $this->createSubClient( 'product/image/' . $type, $name );
	}


	/**
	 * Deletes the removed list items and their referenced items
	 *
	 * @param array $listItems List of items implementing \Aimeos\MShop\Common\Item\Lists\Iface
	 * @param array $listIds List of IDs of the still used list items
	 */
	protected function cleanupItems( array $listItems, array $listIds )
	{
		$context = $this->getContext();
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'media' );
		$listManager = \Aimeos\MShop\Factory::createManager( $context, 'product/lists' );
		$cntl = \Aimeos\Controller\Common\Media\Factory::createController( $context );

		$rmItems = [];
		$rmListIds = array_diff( array_keys( $listItems ), $listIds );

		foreach( $rmListIds as $rmListId )
		{
			if( ( $item = $listItems[$rmListId]->getRefItem() ) !== null ) {
				$rmItems[$item->getId()] = $item;
			}
		}

		$search = $listManager->createSearch();
		$expr = array(
			$search->compare( '==', 'product.lists.refid', array_keys( $rmItems ) ),
			$search->compare( '==', 'product.lists.domain', 'media' ),
			$search->compare( '==', 'product.lists.type.code', 'default' ),
			$search->compare( '==', 'product.lists.type.domain', 'media' ),
		);
		$search->setConditions( $search->combine( '&&', $expr ) );
		$search->setSlice( 0, 0x7fffffff );

		foreach( $listManager->aggregate( $search, 'product.lists.refid' ) as $key => $count )
		{
			if( $count > 1 ) {
				unset( $rmItems[$key] );
			} else {
				$cntl->delete( $rmItems[$key] );
			}
		}

		$listManager->deleteItems( $rmListIds  );
		$manager->deleteItems( array_keys( $rmItems )  );
	}


	/**
	 * Creates a new pre-filled item
	 *
	 * @return \Aimeos\MShop\Media\Item\Iface New media item object
	 */
	protected function createItem()
	{
		$context = $this->getContext();
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'media' );
		$typeManager = \Aimeos\MShop\Factory::createManager( $context, 'media/type' );

		$item = $manager->createItem();
		$item->setTypeId( $typeManager->findItem( 'default', [], 'product' )->getId() );
		$item->setDomain( 'product' );
		$item->setStatus( 1 );

		return $item;
	}


	/**
	 * Creates a new pre-filled list item
	 *
	 * @param string $id Parent ID for the new list item
	 * @return \Aimeos\MShop\Common\Item\Lists\Iface New list item object
	 */
	protected function createListItem( $id )
	{
		$context = $this->getContext();
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'product/lists' );
		$typeManager = \Aimeos\MShop\Factory::createManager( $context, 'product/lists/type' );

		$item = $manager->createItem();
		$item->setTypeId( $typeManager->findItem( 'default', [], 'media' )->getId() );
		$item->setDomain( 'media' );
		$item->setParentId( $id );
		$item->setStatus( 1 );

		return $item;
	}


	/**
	 * Returns the available media types
	 *
	 * @return \Aimeos\MShop\Common\Item\Type\Iface[] Associative list of media type ID as keys and items as values
	 */
	protected function getMediaTypes()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'media/type' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'media.type.domain', 'product' ) );
		$search->setSortations( array( $search->sort( '+', 'media.type.label' ) ) );

		return $manager->searchItems( $search );
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
	 * Creates new and updates existing items using the data array
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $item Product item object without referenced domain items
	 * @param string[] $data Data array
	 */
	protected function fromArray( \Aimeos\MShop\Product\Item\Iface $item, array $data )
	{
		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'product' );
		$mediaManager = \Aimeos\MShop\Factory::createManager( $context, 'media' );
		$listManager = \Aimeos\MShop\Factory::createManager( $context, 'product/lists' );
		$cntl = \Aimeos\Controller\Common\Media\Factory::createController( $context );

		$listIds = (array) $this->getValue( $data, 'product.lists.id', [] );
		$listItems = $manager->getItem( $item->getId(), array( 'media' ) )->getListItems( 'media', 'default' );

		$mediaItem = $this->createItem();
		$listItem = $this->createListItem( $item->getId() );

		$files = $this->getValue( (array) $this->getView()->request()->getUploadedFiles(), 'image/files', [] );

		foreach( $listIds as $idx => $listid )
		{
			if( !isset( $listItems[$listid] ) )
			{
				$litem = clone $listItem;

				if( ( $refId = $this->getValue( $data, 'product.lists.refid/' . $idx ) ) !== null ) {
					$item = $mediaManager->getItem( $refId ); // copy existing item
				} else {
					$item = clone $mediaItem;
				}
			}
			else
			{
				$litem = $listItems[$listid];
				$item = $litem->getRefItem();
			}

			if( ( $file = $this->getValue( $files, $idx ) ) !== null ) {
				$cntl->add( $item, $file );
			}

			$item->setLabel( $this->getValue( $data, 'media.label/' . $idx ) );
			$item->setLanguageId( $this->getValue( $data, 'media.languageid/' . $idx ) );

			$item = $mediaManager->saveItem( $item );

			$litem->setPosition( $idx );
			$litem->setRefId( $item->getId() );

			$listManager->saveItem( $litem, false );
		}

		$this->cleanupItems( $listItems, $listIds );
	}


	/**
	 * Constructs the data array for the view from the given item
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $item Product item object including referenced domain items
	 * @param boolean $copy True if items should be copied, false if not
	 * @return string[] Multi-dimensional associative list of item data
	 */
	protected function toArray( \Aimeos\MShop\Product\Item\Iface $item, $copy = false )
	{
		$data = [];
		$siteId = $this->getContext()->getLocale()->getSiteId();

		foreach( $item->getListItems( 'media', 'default' ) as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) === null ) {
				continue;
			}

			$list = $listItem->toArray( true );

			if( $copy === true )
			{
				$list['product.lists.siteid'] = $siteId;
				$list['product.lists.id'] = '';
			}

			foreach( $list as $key => $value ) {
				$data[$key][] = $value;
			}

			foreach( $refItem->toArray( true ) as $key => $value ) {
				$data[$key][] = $value;
			}
		}

		return $data;
	}


	/**
	 * Returns the rendered template including the view data
	 *
	 * @param \Aimeos\MW\View\Iface $view View object with data assigned
	 * @return string HTML output
	 */
	protected function render( \Aimeos\MW\View\Iface $view )
	{
		/** admin/jqadm/product/image/template-item
		 * Relative path to the HTML body template of the image subpart for products.
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
		$tplconf = 'admin/jqadm/product/image/template-item';
		$default = 'product/item-image-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}
}