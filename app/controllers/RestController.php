<?php
declare(strict_types=1);

use Phalcon\Di;
use Phalcon\Http\Request;
use Phalcon\Paginator\Adapter\QueryBuilder;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;




class RestController extends \Phalcon\Mvc\Controller
{

    protected $logger;

    /**
     * we need a REST response, so disabling views
     */
    public function initialize()
    {
        $this->view->disable();

        $this->logger = DI::getDefault()->getShared('logService');
    }

    /**
     *
     */
    public function indexAction($id)
    {
        $requestedPage     = $this->request->get('page', null, 1);
        $requestedPageSize = $this->request->get('pageSize', null, 2);
        $requestedOffset   = $this->request->get('offset', null, 0);
        $requestedNamePart = $this->request->get('name');

        $query = $this
            ->modelsManager
            ->createBuilder()
            ->from(PhonebookItem::class)
            ->orderBy("phone_number");

        if (!empty($id)) {

            $query->andWhere('id = :requestedId:', ['requestedId' => $id]);

        } else {
            if (!empty($requestedNamePart)) {

                $query->andWhere('first_name LIKE :requestedNamePart: OR last_name LIKE :requestedNamePart:', [
                    'requestedNamePart' => '%' . $requestedNamePart . '%',
                ]);

            }
        }

        $paginator = new QueryBuilder(
            [
                "builder" => $query,
                "limit"   => $requestedPageSize,
                "offset"  => $requestedOffset,
                "page"    => $requestedPage,
            ]
        );

        $paginate = $paginator->paginate();

        $this->response->setJsonContent([
            'status' => 'success',
            'data'   => [
                'items' => $paginate->getItems(),
                'page'  => $paginator->getCurrentPage(),
                'total' => $paginate->getTotalItems(),
            ]
        ])->send();
    }

    /**
     *
     */
    public function createAction()
    {
        $validation = new Validation();

        $validation->add(
            [
                'first_name',
                'phone_number',
            ],
            new PresenceOf(
                [
                    'message' => [
                        'first_name'   => 'The first_name is required',
                        'phone_number' => 'The phone_number is required',
                    ],
                ]
            )
        );

        $postData = json_decode(file_get_contents('php://input'), true);
        unset($postData['inserted_on']);
        unset($postData['updated_on']);

        $validationResult = $validation->validate($postData);

        //IDK why but valid() method returns false on the correct form and true on incorrect
        $isRequestValid = !$validationResult->valid();

        if (!$isRequestValid) {
            $this->response->setStatusCode(400);
            $this->response->setJsonContent([
                'status' => 'error',
                'data'   => $validation->getMessages(),
            ]);

            $this->logger->error($validation->getDefaultMessage());
            return null;
        }

        $phoneBookItemModel = new PhonebookItem($postData);

        if ($phoneBookItemModel->save()) {

            $this->response->setStatusCode(201);
            $this->response->setJsonContent([
                'status' => 'success',
                'data'   => $phoneBookItemModel->toArray(),
            ])->send();

        } else {

            $this->logger->error($phoneBookItemModel->getMessages());

            $this->response->setStatusCode(400);
            $this->response->setJsonContent([
                'status' => 'error',
                'data'   => $phoneBookItemModel->getMessages(),
            ])->send();
        }
    }

    /**
     * @param $id
     *
     * @return null
     */
    public function updateAction($id)
    {
        if (empty($id)) {
            $this->response->setStatusCode(404);

            return null;
        }

        $putData = $this->request->getPut();
        unset($putData['id']);
        unset($putData['inserted_on']);
        unset($putData['updated_on']);

        $phonebookItemModel = PhonebookItem::find(['id' => $id])->getFirst();

        foreach ($putData as $putDatumName => $putDatumValue) {
            $phonebookItemModel->writeAttribute($putDatumName, $putDatumValue);
        }

        if ($phonebookItemModel->update()) {

            $this->response->setJsonContent([
                'status' => 'success',
                'data'   => $phonebookItemModel->toArray(),
            ])->send();

        } else {
            $this->logger->error($phonebookItemModel->getMessages());
            $this->response->setStatusCode(400);
            $this->response->setJsonContent([
                'status' => 'error',
                'data'   => $phonebookItemModel->getMessages(),
            ])->send();
        }
    }

    /**
     * @return null
     */
    public function deleteAction()
    {
        $validation = new Validation();

        $validation->add(
            [
                'id',
            ],
            new PresenceOf(
                [
                    'message' => [
                        'id' => 'The id is required',
                    ],
                ]
            )
        );

        $deleteData = $this->request->get();

        $validationResult = $validation->validate($deleteData);
        $isRequestValid   = !$validationResult->valid();

        $this->logger->error($validation->getMessages());
        if (!$isRequestValid) {
            $this->response->setStatusCode(400);
            $this->response->setJsonContent([
                'status' => 'error',
                'data'   => $validation->getMessages(),
            ]);

            return null;
        }

        $phonebookItemModel = PhonebookItem::find(['id' => $deleteData['id']])->getFirst();

        $this->logger->error($phonebookItemModel->getMessages());
        if (empty($phonebookItemModel)) {
            $this->response->setStatusCode(400);
            $this->response->setJsonContent([
                'status' => 'error',
                'data'   => $phonebookItemModel->getMessages(),
            ])->send();
        }

        if ($phonebookItemModel->delete()) {

            $this->response->setJsonContent([
                'status' => 'success',
            ])->send();

        } else {

            $this->logger->error($phonebookItemModel->getMessages());
            $this->response->setStatusCode(400);
            $this->response->setJsonContent([
                'status' => 'error',
                'data'   => $phonebookItemModel->getMessages(),
            ])->send();
        }
    }
}

