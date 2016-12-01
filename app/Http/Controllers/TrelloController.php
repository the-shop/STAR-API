<?php

namespace App\Http\Controllers;

use Faker\Provider\zh_TW\DateTime;
use Illuminate\Http\Request;

use App\Http\Requests;

use Trello\Client;

class TrelloController extends Controller
{
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
        $boardID = $this->getBoardId($name);

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

        $idlist = $this->getBoardId();

        if (!in_array($id, $idlist)) {
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
     * @param $id
     * @param $name
     * @param $description
     */
    public function createTicket(Request $request = null, $id, $name = null, $description = null)
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
        if (empty($name)) {
            $errors[] = 'Invalid name field.';

        }

        if (empty($description)) {
            $errors[] = 'Invalid description field.';
        }

        if (count($errors) > 0) {
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
    private function getBoardId($name = null)
    {
        $trello = $this->instance();
        $list = $trello->api('member')->boards()->all('info1x2');

        //get ID for a single Board
        if ($name !== null) {
            $id = '';
            foreach ($list as $board) {
                if ($board['name'] === $name) {
                    $id .= $board['id'];
                }
            }

            return $id;
        }

        //get all Boards ID's
        $id = [];
        foreach ($list as $board) {
            $id[] = $board['id'];
        }

        return $id;
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
        $boardslist = $this->getBoardId();
        if (!in_array($boardId, $boardslist)) {
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
        if(!in_array($cardId, $cards)) {
            $errors[] = 'Invalid Card ID.';
        }

        //get all board members
        $memberslist = [];
        $members = $trello->boards()->members()->all($boardId);
        foreach ($members as $member) {
            $memberslist[] = $member['id'];
        }

        //check if members exists on board
        if(!in_array($memberId, $memberslist)) {
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
        $lists = [];

        //generate list of list-names and ID's
        $boardlists = $trello->boards()->lists()->all($id);
        foreach ($boardlists as $list) {
            $lists[] = [$list['name'] => $list['id']];
        }

        //generate predefined cards from config/trello
        $cards = \Config::get('trello.cards');
        foreach ($lists as $list) {
            foreach ($cards as $card) {
                if (key_exists($card['list'], $list)) {
                    $this->createTicket(null, $list[$card['list']], $card['name'], $card['description']);
                }
            }
        }

        return true;
    }
}
