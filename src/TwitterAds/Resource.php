<?php

namespace Hborras\TwitterAdsSDK\TwitterAds;

use Hborras\TwitterAdsSDK\TwitterAds\Errors\ServerError;
use Hborras\TwitterAdsSDK\TwitterAdsException;
use Hborras\TwitterAdsSDK\Arrayable;

/**
 * Created by PhpStorm.
 * User: hborras
 * Date: 2/04/16
 * Time: 12:17.
 */
abstract class Resource implements Arrayable
{
    use \Hborras\TwitterAdsSDK\DateTime\DateTimeFormatter;

    const RESOURCE            = '';
    const RESOURCE_COLLECTION = '';
    const RESOURCE_ID_REPLACE = '{id}';
    const RESOURCE_REPLACE    = '{account_id}';

    /** @var  Account $account */
    private $account;
    private $properties = [];

    abstract public function getId();

    /**
     * Automatically set the account if this class is not an account
     * Resource constructor.
     * @param Account|null $account
     */
    public function __construct(Account $account = null)
    {
        if (get_class($this) != Account::class) {
            $this->setAccount($account);
        }
    }

    /**
     * Returns a Cursor instance for a given resource.
     *
     * @param $params
     *
     * @return Cursor
     */
    public function all($params = [])
    {
        $resource = str_replace(static::RESOURCE_REPLACE, $this->account->getId(), static::RESOURCE_COLLECTION);
        $response = $this->account->getTwitterAds()->get($resource, $params);

        return new Cursor($this, $this->account, $response->getBody(), $params);
    }

    /**
     * Returns an object instance for a given resource.
     *
     * @param string $id
     * @param $params
     *
     * @return Resource
     */
    public function load($id, $params = [])
    {
        $resource = str_replace(static::RESOURCE_REPLACE, $this->account->getId(), static::RESOURCE);
        $resource = str_replace(static::RESOURCE_ID_REPLACE, $id, $resource);
        $response = $this->account->getTwitterAds()->get($resource, $params);

        return $this->fromResponse($response->getBody()->data);
    }

    /**
     * Reloads all attributes for the current object instance from the API.
     *
     * @param $params
     * @return Resource
     * @throws ServerError
     */
    public function reload($params = [])
    {
        if (!$this->getId()) {
            throw new ServerError(TwitterAdsException::SERVER_ERROR, "Error loading entity", null, null);
        }

        $resource = str_replace(static::RESOURCE_REPLACE, $this->account->getId(), static::RESOURCE);
        $resource = str_replace(static::RESOURCE_ID_REPLACE, $this->getId(), $resource);
        $response = $this->account->getTwitterAds()->get($resource, $params);

        return $this->fromResponse($response->getBody()->data);
    }

    /**
     * Populates a given objects attributes from a parsed JSON API response.
     * This helper handles all necessary type coercions as it assigns attribute values.
     *
     * @param $response
     *
     * @return $this
     */
    public function fromResponse($response)
    {
        foreach (get_object_vars($response) as $key => $value) {
            if (($key == 'created_at' || $key == 'updated_at' || $key == 'start_time' || $key == 'end_time' || $key == 'timezone_switch_at') && !is_null($value)) {
                $this->$key = $this->toDateTimeImmutable($value);
            } else {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Generates a Hash of property values for the current object. This helper
     * handles all necessary type coercions as it generates its output.
     */
    public function toParams()
    {
        $params = [];
        foreach ($this->getProperties() as $property) {
            if (is_null($this->$property)) {
                continue;
            }
            if ($this->$property instanceof \DateTimeInterface) {
                $params[$property] = $this->$property->format('c');
            } elseif (is_array($this->$property)) {
                $params[$property] = implode(',', $this->$property);
            } elseif (is_bool($this->$property)) {
                $params[$property] = $this->$property ? 'true' : 'false';
            } else {
                $params[$property] = strval($this->$property);
            }
        }

        return $params;
    }

    public function validateLoaded()
    {
        if (!$this->getId()) {
            throw new ServerError(TwitterAdsException::SERVER_ERROR, "Error loading entity", null, null);
        }
    }

    public function toArray()
    {
        $data = [];
        $vars = get_object_vars($this);

        foreach ($vars as $key => $var) {
            if ($var instanceof $this) {
                continue;
            }
            $data[$key] = $var;
        }

        return $data;
    }

    public function  loadResource(Account $account, $id = '', $params = [])
    {
        $this->setAccount($account);
        $account->validateLoaded();
        if ($id != '') {
            return $this->load($id, $params);
        } else {
            return $this->all($params);
        }
    }

    /**
     * Saves or updates the current object instance depending on the
     * presence of `object->getId()`.
     */
    public function save()
    {
        if ($this->getId()) {
            $resource = str_replace(static::RESOURCE_REPLACE, $this->account->getId(), static::RESOURCE);
            $resource = str_replace(static::RESOURCE_ID_REPLACE, $this->getId(), $resource);
            $response = $this->account->getTwitterAds()->put($resource, $this->toParams());
        } else {
            $resource = str_replace(static::RESOURCE_REPLACE, $this->account->getId(), static::RESOURCE_COLLECTION);
            $response = $this->account->getTwitterAds()->post($resource, $this->toParams());
        }

        return $this->fromResponse($response->getBody()->data);
    }

    /**
     * Deletes the current object instance depending on the
     * presence of `object->getId()`.
     */
    public function delete()
    {
        $resource = str_replace(static::RESOURCE_REPLACE, $this->account->getId(), static::RESOURCE);
        $resource = str_replace(static::RESOURCE_ID_REPLACE, $this->getId(), $resource);
        $response = $this->account->getTwitterAds()->delete($resource);
        $this->fromResponse($response->getBody()->data);
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     */
    public function setAccount($account = null)
    {
        $this->account = $account;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}
