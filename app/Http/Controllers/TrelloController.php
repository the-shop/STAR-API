<?php

namespace App\Http\Controllers;

use Faker\Provider\zh_TW\DateTime;
use Illuminate\Http\Request;

use App\Http\Requests;

use Trello\Client;

class TrelloController extends Controller
{
    /**
     * Get boards ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBoardsId()
    {
        $boards = $this->boardsId();

        return $this->jsonSuccess($boards);
    }

    /**
     * Get lists ID
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListsId($id = null)
    {
        $lists = $this->listsId($id);

        if ($lists === false) {
            return $this->jsonError(['Invalid board id.'], 404);
        }

        return $this->jsonSuccess($lists);
    }

    /**
     * Get tickets ID
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTicketsId($boardId = null, $id = null)
    {
        $errors = [];
        $cards = $this->ticketsId($boardId, $id, $errors);

        if ($cards === false) {
            return $this->jsonError($errors, 404);
        }

        return $this->jsonSuccess($cards);
    }

    /**
     * Get members ID
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMembersId($id = null)
    {
        $members = $this->membersId($id);

        if ($members === false) {
            return $this->jsonError(['Invalid board ID.'], 404);
        }

        return $this->jsonSuccess($members);
    }

    /**
     * Create new Board
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createBoard(Request $request)
    {
        $name = $request->input('name');
        $predefined = $request->input('predefined');
        $trello = $this->instance();

        //validate name field
        if (empty($name)) {
            return $this->jsonError(['Empty name field.'], 400);
        }

        //validate predefined field
        if ($predefined !== 'yes') {
            $trello->boards()->create(['name' => $name, 'defaultLists' => true]);

            return $this->jsonSuccess([
                'created' => true,
                'board name' => $name
            ]);
        }

        //create new board
        $trello->boards()->create(['name' => $name, 'defaultLists' => false]);
        $boardID = $this->boardsId($name);

        //check if config/trello has lists defined to generate predefined lists and cards
        $lists = \Config::get('trello.lists');
        if (!empty($lists)) {
            $this->generateMultipleLists($boardID, $lists);
            $this->generateMultipleCards($boardID);
        }

        return $this->jsonSuccess([
            'created' => true,
            'board name' => $name
        ]);
    }

    /**
     * Create new list
     * @param $id
     * @param $name
     */
    public function createList(Request $request = null, $id = null, $name = null)
    {
        if ($request !== null) {
            if (\Request::has('name')) {
                $name = $request->input('name');
            }
        }

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Invalid name field.';
        }

        $boards = $this->boardsId();

        if (!key_exists($id, $boards)) {
            $errors[] = 'Invalid board ID.';
        }

        if (count($errors) > 0) {
            return $this->jsonError($errors, 400);
        }

        $trello = $this->instance();
        $trello->boards()->lists()->create($id, ['name' => $name]);

        return $this->jsonSuccess([
            'created' => true,
            'list name' => $name
        ]);

    }

    /**
     * Create new ticket
     * @param Request|null $request
     * @param $boardId
     * @param $id
     * @param null $name
     * @param null $description
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTicket(Request $request = null, $boardId, $id, $name = null, $description = null)
    {
        if ($request !== null) {
            if (\Request::has('name')) {
                $name = $request->input('name');
            }
            if (\Request::has('description')) {
                $description = $request->input('description');
            }
        }

        $errors = [];
        $validator = $this->validateCreateTicket($boardId, $id, $name, $description, $errors);

        if ($validator === false) {
            return $this->jsonError($errors, 400);
        }

        $trello = $this->instance();
        $trello->lists()->cards()->create($id, $name, ['desc' => $description]);

        return $this->jsonSuccess([
            'created' => 'true',
            'ticket name' => $name,
            'description' => $description
        ]);
    }

    /**
     * Assign Member to ticket
     * @param $boardId
     * @param $cardId
     * @param $memberId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignMember($boardId, $cardId, $memberId)
    {
        $errors = [];
        $validation = $this->validateCardMember($boardId, $cardId, $memberId, $errors);

        if ($validation === false) {
            return $this->jsonError($errors, 400);
        }

        $trello = $this->instance();
        $trello->cards()->members()->add($cardId, $memberId);

        return $this->jsonSuccess([
            'member added' => true
        ]);

    }

    /**
     * Remove member from ticket
     * @param $boardId
     * @param $cardId
     * @param $memberId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember($boardId, $cardId, $memberId)
    {
        $errors = [];
        $validation = $this->validateCardMember($boardId, $cardId, $memberId, $errors);

        if ($validation === false) {
            return $this->jsonError($errors, 400);
        }

        $trello = $this->instance();
        $trello->cards()->members()->remove($cardId, $memberId);

        return $this->jsonSuccess([
            'member removed' => true
        ]);

    }

    /**
     * Set Card's due date
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setDueDate(Request $request, $id)
    {
        $date = $request->input('date');
        $errors = [];
        if (empty($date)) {
            $errors[] = 'Empty date field.';
        }

        if (count($errors) > 0) {
            return $this->jsonError($errors, 400);
        }

        $datetime = new \DateTime($date);
        $trello = $this->instance();
        $trello->cards()->setDueDate($id, $datetime);

        return $this->jsonSuccess([
            'due date set' => true,
            'due date' => $datetime
        ]);

    }

    /**
     * Instantiate Trello Client
     * @return Client
     */
    private function instance()
    {
        $client = new Client();
        $client->authenticate('5c4e5563774606265cdec5c3f34c78ce', 'a1f850650b3ed5fc60c7651dc9aea134512a0fcf22265e0e9c25ef999288cdb0', Client::AUTH_URL_CLIENT_ID);

        return $client;
    }

    /**
     * Get Board ID
     * @param null $name
     * @return array|string
     */
    private function boardsId($name = null)
    {
        $trello = $this->instance();
        $boards = $trello->api('member')->boards()->all('info1x2');

        //get ID for a single Board based on name
        if ($name !== null) {
            $id = '';
            foreach ($boards as $board) {
                if ($board['name'] === $name) {
                    $id .= $board['id'];
                }
            }

            return $id;
        }

        //get all Boards ID's
        $id = [];
        foreach ($boards as $board) {
            $id[$board['id']] = $board['name'];
        }

        return $id;
    }

    /**
     * List validation and get list Id's
     * @param null $id
     * @param $errors
     * @return array|bool
     */
    private function listsId($id = null)
    {
        if ($id === null) {
            return false;
        }

        $boards = $this->boardsId();
        if (!key_exists($id, $boards)) {
            return false;
        }

        $trello = $this->instance();
        $lists = [];
        $alllists = $trello->boards()->lists()->all($id);
        foreach ($alllists as $list) {
            $lists[$list['id']] = $list['name'];
        }

        return $lists;
    }

    /**
     * Ticket validation and get tickets ID
     * @param null $boardId
     * @param null $id
     * @param $errors
     * @return array|bool
     */
    private function ticketsId($boardId = null, $id = null, &$errors)
    {
        if ($boardId === null) {
            $errors[] = 'Invalid board ID.';
            return false;
        }

        $boards = $this->boardsId();
        if (!key_exists($boardId, $boards)) {
            $errors[] = 'Invalid board ID.';
        }

        if ($id === null) {
            $errors[] = 'Invalid list ID.';
        }

        $lists = $this->listsId($boardId);
        if ($lists === false) {
            $errors[] = 'Invalid list ID.';
        } elseif (!key_exists($id, $lists)) {
            $errors[] = 'Invalid list ID.';
        }

        if (count($errors) > 0) {
            return false;
        }

        $trello = $this->instance();
        $tickets = [];
        $alltickets = $trello->lists()->cards()->all($id);
        foreach ($alltickets as $ticket) {
            $tickets[$ticket['id']] = $ticket['name'];
        }

        return $tickets;
    }

    private function membersId($id = null)
    {
        if ($id === null) {
            return false;
        }

        $boards = $this->boardsId();
        if (!key_exists($id, $boards)) {
            return false;
        }

        $trello = $this->instance();
        $members = [];
        $allmembers = $trello->boards()->members()->all($id);
        foreach ($allmembers as $member) {
            $members[$member['id']] = $member['username'];
        }

        return $members;
    }

    /**
     * Validate ticket creation
     * @param $boardId
     * @param $id
     * @param $errors
     * @return bool
     */
    private function validateCreateTicket($boardId, $id, $name, $description, &$errors)
    {
        $boards = $this->boardsId();
        if (!key_exists($boardId, $boards)) {
            $errors[] = 'Invalid board ID.';
        } else {
            $lists = $this->listsId($boardId);
            if ($lists === false) {
                $errors[] = 'Invalid list ID.';
            } elseif (!key_exists($id, $lists)) {
                $errors[] = 'Invalid list ID.';
            }
        }

        if (empty($name)) {
            $errors[] = 'Invalid name field.';

        }

        if (empty($description)) {
            $errors[] = 'Invalid description field.';
        }

        if (count($errors) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Validator for assign member and remove member from card
     * @param $boardId
     * @param $cardId
     * @param $memberId
     * @param $errors
     * @return bool
     */
    private function validateCardMember($boardId, $cardId, $memberId, &$errors)
    {
        //check if board id exist
        $boardslist = $this->boardsId();
        if (!key_exists($boardId, $boardslist)) {
            $errors[] = 'Invalid board ID.';
            return false;
        }

        $trello = $this->instance();

        //get all board cards
        $cards = [];
        $cardlist = $trello->boards()->cards()->all($boardId);

        foreach ($cardlist as $card) {
            $cards[] = $card['id'];
        }

        //check if card exist on board
        if (!in_array($cardId, $cards)) {
            $errors[] = 'Invalid Card ID.';
        }

        //get all board members
        $memberslist = [];
        $members = $trello->boards()->members()->all($boardId);
        foreach ($members as $member) {
            $memberslist[] = $member['id'];
        }

        //check if members exists on board
        if (!in_array($memberId, $memberslist)) {
            $errors[] = 'Invalid Member ID.';
        }

        if (count($errors) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Generate predefined board lists
     * @param $id
     * @param $names
     * @return bool
     */
    private function generateMultipleLists($id, $names)
    {
        foreach ($names as $name) {
            $this->createList(null, $id, $name);
        }

        return true;
    }

    /**
     * Generate predefined board list cards
     * @param $id
     * @return bool
     */
    private function generateMultipleCards($id)
    {
        $trello = $this->instance();
        $lists  = $this->listsId($id);

        //generate predefined cards from config/trello
        $cards = \Config::get('trello.cards');
        foreach ($lists as $key => $value) {
            foreach ($cards as $card) {
                if ($card['list'] === $value) {
                    $this->createTicket(null, $id, $key, $card['name'], $card['description']);
                }
            }
        }

        return true;
    }
}
