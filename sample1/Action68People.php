<?php

namespace Maraquia\Partners;

use Maraquia\Model\People as PeopleModel;
use Maraquia\Model\PeopleSocials;
use Maraquia\Payments\Multibonus\Api;

class Action68People {

    /**
     * Constant for social network name
     */
    private const SOCIALNETWORK = 'multibonus';

    /**
     * People Model
     *
     * @var PeopleModel
     */
    private ?PeopleModel $_people;

    /**
     * Multibonus API
     *
     * @var Api
     */
    private Api $_api;


    public function __construct()
    {
        $this->_api = new Api(true);
    }


    /**
     * Get people model
     *
     * @return PeopleModel|null
     */
    public function getPeople(): ?PeopleModel
    {
        return $this->_people;
    }


    /**
     * Check user by UserTicket
     *
     * @param string $userTicket
     * @return bool
     * @throws \Exception
     */
    public function check(string $userTicket): bool {

        $response = $this->_api->validateUser($userTicket);
        if (empty($response)){
            return false;
        }

        $em = \Doctrine::getEntityManager();
        $em->getConnection()->setTransactionIsolation(\Doctrine\DBAL\TransactionIsolationLevel::SERIALIZABLE);
        $em->getConnection()->beginTransaction();

        $this->_people = $this->getFromResponse($response);

        $em->getConnection()->commit();


        return (bool)$this->_people;

    }

    /**
     * Get people from response data
     *
     * @param array $response
     * @return null|PeopleModel
     * @throws \Exception
     */
    private function getFromResponse(array $response): ?PeopleModel{


        $people = null;

        $peopleSocial = PeopleSocials::getRepository()->findOneBy(["network" => $this::SOCIALNETWORK, "socialId" => $response["ClientId"]]);
        if ($peopleSocial){
            $people = $peopleSocial->people;
        } elseif (!empty($response["Email"])){
            # Ищем пользователя по почте
            $people = PeopleModel::getRepository()->findOneByEmail($response["Email"]);
        }

        if (empty($people)){
            $people = $this->registerFromApiResponse($response);
        }

        if (empty($people)){
            return null;
        }

        if ($people->getActive() != 'Y'){
            $people->setActive('Y');
            $people->save();
        }

        if (!$peopleSocial){
            $this->createSocialFromApiResponse($people, $response);
        }

        return $people;
    }

    /**
     * Create people social data from API response
     *
     * @param PeopleModel $people
     * @param array $response
     * @return PeopleSocials
     * @throws \Exception
     */
    private function createSocialFromApiResponse(PeopleModel $people, array $response): PeopleSocials
    {
        $peopleSocial = new PeopleSocials();
        $peopleSocial->network = $this::SOCIALNETWORK;
        $peopleSocial->people = $people;
        $peopleSocial->socialId = $response["ClientId"];
        $peopleSocial->save();
        return $peopleSocial;
    }

    /**
     * Register people from API response
     *
     * @param array $response
     * @return PeopleModel|null
     */
    private function registerFromApiResponse(array $response): ?PeopleModel {
        $people = \Maraquia\PeopleWork::register([
            'first_name' => trim($response['FirstName']),
            'second_name' => trim($response['LastName']),
            'email' => $response["Email"],
            'active' => 'Y',
        ], true);

        return $people === false ? null : $people;
    }

    /**
     * Login people
     *
     * @param array $params
     * @return bool
     */
    public function login(array $params): bool {
        if (empty($this->_people)){
            return false;
        }
        $auth = \Auth::getInstance('People');
        $auth->login($this->_people->getId(), false);
        foreach ($params as $var => $value){
            $auth->set($var, $value);
        }
        return true;
    }

}