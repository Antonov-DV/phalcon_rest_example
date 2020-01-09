<?php

use Phalcon\Di;
use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Validation;
use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\Uniqueness;
use Phalcon\Cache;
use Phalcon\Cache\AdapterFactory;
use Phalcon\Storage\SerializerFactory;

class PhonebookItem extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $first_name;

    /**
     *
     * @var string
     */
    public $last_name;

    /**
     *
     * @var string
     */
    public $phone_number;

    /**
     *
     * @var string
     */
    public $country_code;

    /**
     *
     * @var string
     */
    public $timezone_name;

    /**
     *
     * @var string
     */
    public $inserted_on;

    /**
     *
     * @var string
     */
    public $updated_on;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("d_phalcon");
        $this->setSource("phonebook_item");

        $this->addBehavior(
            new Timestampable(
                [
                    "beforeCreate" => [
                        "field"  => "inserted_on",
                        "format" => "Y-m-d h:i:s",
                    ],
                ]
            )
        );

        $this->addBehavior(
            new Timestampable(
                [
                    "beforeUpdate" => [
                        "field"  => "updated_on",
                        "format" => "Y-m-d h:i:s",
                    ],
                ]
            )
        );
    }

    /**
     * @return bool
     */
    public function validation()
    {
        $validator = new Validation();

        $validator->add('country_code', new InclusionIn([
                'domain'  => $this->getValidCountryCodes(),
                'message' => 'Invalid country code',
            ])
        );

        $validator->add('timezone_name', new InclusionIn([
                'domain'  => $this->getValidTimezoneNames(),
                'message' => 'Invalid timezone name',
            ])
        );

        $validator->add('phone_number', new Uniqueness([
            'message' => 'This phone number is already registered',
        ]));


        $validator->add('phone_number', new Regex([
            'pattern' => '/^(\(?\+?[0-9]*\)?)?[0-9_\- \(\)]*$/',
            'message' => 'The phone number is invalid',
        ]));

        return $this->validate($validator);
    }

    /**
     * @return Cache
     */
    protected function getCache()
    {

        $cache = DI::getDefault()->getShared('apiCache');

        return $cache;
    }

    /**
     * Returns list of valid country codes
     * @return array
     */
    protected function getValidCountryCodes()
    {
        $cache = $this->getCache();

        $countries = $cache->get('countriesHttpCache');

        if (empty($countries)) {

            $client = new \GuzzleHttp\Client();

            $requestData = $client->request('GET', 'https://api.hostaway.com/countries');

            if ($requestData->getStatusCode() === 200) {

                $requestDataArray = json_decode($requestData->getBody()->getContents(), true);

                $countries = $requestDataArray['result'];

                $cache->set('countriesHttpCache', $countries);

                return array_keys($countries);
            }

            return [];
        }

        return array_keys($countries);
    }

    /**
     * Returns list of valid timezone names
     * @return array
     */
    protected function getValidTimezoneNames()
    {
        $cache = $this->getCache();

        $timezones = $cache->get('timezonesHttpCache');

        if (empty($timezones)) {

            $client = new \GuzzleHttp\Client();

            $requestData = $client->request('GET', 'https://api.hostaway.com/timezones');

            if ($requestData->getStatusCode() === 200) {

                $requestDataArray = json_decode($requestData->getBody()->getContents(), true);

                $timezones = $requestDataArray['result'];

                $cache->set('timezonesHttpCache', $timezones);

                return array_keys($timezones);
            }

            return [];
        }

        return array_keys($timezones);
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     *
     * @return PhonebookItem[]|PhonebookItem|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     *
     * @return PhonebookItem|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
