<?php
/**
 * Orm
 * @author Petr Procházka (petr@petrp.cz)
 * @license "New" BSD License
 */

namespace Orm;

use RuntimeException;

/**
 * Entity is not attached to repository.
 * Requested operation requires it.
 * Call IRepository::attach() or IRepository::persist() first.
 * @see IRepository::attach()
 * @see IRepository::persist()
 * @author Petr Procházka
 * @package Orm
 * @subpackage Entity\Exceptions
 */
class EntityNotAttachedException extends RuntimeException
{

}
