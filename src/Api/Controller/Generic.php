<?php
/**
 * This is a generic Maleficarum API Controller base class. Provides convenience logic to actual controllers defined in services.
 */
declare (strict_types=1);

namespace Maleficarum\Api\Controller;

abstract class Generic {

    /* ------------------------------------ Class Traits START ----------------------------------------- */

    /**
     * \Maleficarum\Config\Dependant
     */
    use \Maleficarum\Config\Dependant;

    /**
     * \Maleficarum\Environment\Dependant
     */
    use \Maleficarum\Environment\Dependant;

    /**
     * \Maleficarum\Request\Dependant
     */
    use \Maleficarum\Request\Dependant;

    /**
     * \Maleficarum\Response\Dependant
     */
    use \Maleficarum\Response\Dependant;

    /* ------------------------------------ Class Traits END ------------------------------------------- */

    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * This value represents a max value the limit parameter can be set to within requests. Static protected
     * instead of const since we want to allow specific controllers to override this if necessary.
     * 
     * @var integer
     */
    protected static $maxLimit = 100;

    /**
     * This will be overwritten by specific controllers to allow the use of \Maleficarum\Api\Controller\Generic::validateSorting()
     *
     * @var array
     * EXAMPLE:
     *  [
     *      'listAction' => [
     *          '-createdAt' => [['orderCreatedAt','DESC']],
     *          '+createdAt' => [['orderCreatedAt','ASC']]
     *      ]
     *  ]
     */
    protected static $sortMap = [];
    
    /* ------------------------------------ Class Property END ----------------------------------------- */
    
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Perform URL to class method remapping.
     *
     * @param string $method
     *
     * @return mixed
     * @throws \Maleficarum\Exception\NotFoundException
     */
    public function __remap(string $method) {
        $action = $method . 'Action';

        if (method_exists($this, $action)) {
            $this->{$action}();
        } else {
            $this->respondToNotFound('404 - page not found.');
        }

        return true;
    }

    /**
     * Immediately halt all actions and send a 400 Bad Request response with provided errors.
     *
     * @param array $errors
     *
     * @return void
     * @throws \Maleficarum\Exception\BadRequestException
     */
    protected function respondToBadRequest(array $errors = []) {
        throw (new \Maleficarum\Exception\BadRequestException())->setErrors($errors);
    }

    /**
     * Immediately halt all actions and send a 401 Unauthorized response.
     *
     * @param string $message
     *
     * @return void
     * @throws \Maleficarum\Exception\UnauthorizedException
     */
    protected function respondToUnauthorized(string $message) {
        throw new \Maleficarum\Exception\UnauthorizedException($message);
    }

    /**
     * Immediately halt all actions and send a 404 Not found response.
     *
     * @param string $message
     *
     * @return void
     * @throws \Maleficarum\Exception\NotFoundException
     */
    protected function respondToNotFound(string $message) {
        throw new \Maleficarum\Exception\NotFoundException($message);
    }

    /**
     * Immediately halt all actions and send a 409 Conflict response with provided errors.
     *
     * @param array $errors
     *
     * @return void
     * @throws \Maleficarum\Exception\ConflictException
     */
    protected function respondToConflict(array $errors = []) {
        throw (new \Maleficarum\Exception\ConflictException())->setErrors($errors);
    }

    /**
     * Validate request sort option - must match what is defined in self::$sortMap[$subset]
     *
     * @param string $subset
     * @throws \Maleficarum\Exception\BadRequestException
     * @return \Maleficarum\Api\Controller\Generic
     */
    protected function validateSorting(string $subset) : \Maleficarum\Api\Controller\Generic {
        // validate if can actually run sorting validation for the specified subset
        is_array(static::$sortMap[$subset]) or $this->respondToBadRequest(['Invalid `sort` parameter - unsupported value.']);

        // sort - must belong to a predefined set of values (static::$sortMap) if provided (LSB)
        (!is_null($this->getRequest()->sort) && !array_key_exists($this->getRequest()->sort, static::$sortMap[$subset])) and $this->respondToBadRequest(['Invalid `sort` parameter - unsupported value.']);

        return $this;
    }
    
    /**
     * Validate request limit/offset data.
     *
     * @throws \Maleficarum\Exception\BadRequestException
     * @return \Maleficarum\Api\Controller\Generic
     */
    protected function validatePagination() : \Maleficarum\Api\Controller\Generic {
        // limit - must be a positive integer if provided between 1 and COLLECTION_MAX_LIMIT
        (
            !is_null($this->getRequest()->limit) &&
            filter_var($this->getRequest()->limit, \FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>static::$maxLimit]]) === false
        ) and $this->respondToBadRequest(['Invalid `limit` parameter - unsupported value.']);

        // offset - must be a non-negative integer if provided
        (
            !is_null($this->getRequest()->offset) && 
            filter_var($this->getRequest()->offset, \FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]) === false
        ) and $this->respondToBadRequest(['Invalid `offset` parameter - unsupported value.']);

        return $this;
    }

    /**
     * Validates and returns given request parameter as an integer or causes a "Bad request" response.
     *
     * @param string $paramName eg. 'orderId'
     * @return int given parameter value as an integer
     * @throws \Maleficarum\Exception\BadRequestException if given parameter is missing or has invalid format
     */
    protected function getIntegerParameter(string $paramName) : int {
        // define common response comment
        $intInfo = 'It must be 64-bit integer between ' . \PHP_INT_MIN . ' and ' . \PHP_INT_MAX;
        
        // check if parameter was defined 
        is_null($this->getRequest()->{$paramName}) and $this->respondToBadRequest(["`$paramName` is required but missing. $intInfo"]);
        
        // filter request value
        $paramValue = filter_var($this->getRequest()->{$paramName}, \FILTER_VALIDATE_INT);
        
        // check if it was an actual integer
        (false === $paramValue) and $this->respondToBadRequest(["Invalid `$paramName` value. $intInfo"]);
        
        return (int)$paramValue;
    }
    
    /* ------------------------------------ Class Methods END ------------------------------------------ */
    
}
