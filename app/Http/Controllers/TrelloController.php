<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Trello\Client;
use Trello\Exception\RuntimeException;

class TrelloController extends Controller
{
    /**
     * Get board IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBoardIds()
    {
        $boards = $this->boardIds();

        return $this->jsonSuccess($boards);
    }

    /**
     * Get list IDs
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListIds($id = null)
    {
        $lists = $this->listIds($id);

        if ($lists === false) {
            return $this->jsonError(['Invalid board id.'], 404);
        }

        return $this->jsonSuccess($lists);
    }

    /**
     * Get ticket IDs
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTicketIds($boardId = null, $id = null)
    {
        $errors = [];
        $cards = $this->ticketIds($boardId, $id, $errors);

        if ($cards === false) {
            return $this->jsonError($errors, 404);
        }

        return $this->jsonSuccess($cards);
    }

    /**
     * Get member IDs
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMemberIds($id = null)
    {
        $members = $this->memberIds($id);

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

        //validate name field
        if (empty($name)) {
            return $this->jsonError(['Empty name field.'], 400);
        }

        $validator = $this->validateCreateBoard($name);
        if ($validator !== false) {
            return $validator;
        }

        $trello = $this->instance();

        //validate predefined field - create board
        if ($predefined !== 'yes') {
            $trello->boards()->create(['name' => $name, 'defaultLists' => true]);

            return $this->jsonSuccess([
                'created' => true,
                'boardName' => $name
            ]);
        }

        //create new predefined board
        $trello->boards()->create(['name' => $name, 'defaultLists' => false]);

        // Check $response for ID
        $boardID = $this->boardIds($name);

        //check if config/trello has lists defined to generate predefined lists and cards
        $lists = \Config::get('trello.lists');
        if (!empty($lists)) {
            $this->generateMultipleLists($boardID, $lists);
            $this->generateMultipleCards($boardID);
        }

        return $this->jsonSuccess([
            'created' => true,
            'boardName' => $name
        ]);
    }

    /**
     * Create new list
     * @param Request|null $request
     * @param null $id
     * @param null $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function createList(Request $request = null, $id = null, $name = null)
    {
        if ($request instanceof Request) {
            $name = $request->input('name');
        }

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Invalid name field.';
        }

        $boards = $this->boardIds();

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
            'listName' => $name
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
        if ($request instanceof Request) {
            $name = $request->input('name', null);
            $description = $request->input('description', null);
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
            'ticketName' => $name,
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

        try {
            $trello->cards()->members()->add($cardId, $memberId);
        } catch (RuntimeException $e) {
            // Still good, member already a member
        }

        return $this->jsonSuccess([
            'memberAdded' => true
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
        try {
            $trello->cards()->members()->remove($cardId, $memberId);
        } catch (RuntimeException $e) {
            //still good, member not on the ticket
        }


        return $this->jsonSuccess([
            'memberRemoved' => true
        ]);
    }

    /**
     * Set Card's due date
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setDueDate(Request $request, $boardId, $id)
    {
        $date = $request->input('date');
        $errors = [];

        $datetime = $this->validateSetDueDate($date, $boardId, $id, $errors);

        if ($datetime === false) {
            return $this->jsonError($errors, 400);
        }

        $trello = $this->instance();
        $trello->cards()->setDueDate($id, $datetime);

        return $this->jsonSuccess([
            'dueDateSet' => true,
            'dueDate' => $datetime
        ]);
    }

    /**
     * Instantiate Trello Client
     * @return Client
     */
    private function instance()
    {
        $trelloKey    = \Config::get('trello.trello_key');
        $trelloSecret = \Config::get('trello.trello_secret');

        $client = new Client();
        $client->authenticate($trelloKey, $trelloSecret, Client::AUTH_URL_CLIENT_ID);

        return $client;
    }

    /**
     * Get Board ID
     * @param null $name
     * @return array|string
     */
    private function boardIds($name = null)
    {
        $trello = $this->instance();
        $username = \Config::get('trello.trello_username');
        $boards = $trello->api('member')->boards()->all($username);

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

        //get all Board IDs
        $id = [];
        foreach ($boards as $board) {
            $id[$board['id']] = $board['name'];
        }

        return $id;
    }

    /**
     * List validation and get list Id's
     * @param null $id
     * @return array|bool
     */
    private function listIds($id = null)
    {
        if ($id === null) {
            return false;
        }

        $boards = $this->boardIds();
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
    // @codingStandardsIgnoreLine
    private function ticketIds($boardId = null, $id = null, &$errors)
    {
        if ($boardId === null) {
            $errors[] = 'Invalid board ID.';
            return false;
        }

        $boards = $this->boardIds();
        if (!key_exists($boardId, $boards)) {
            $errors[] = 'Invalid board ID.';
        }

        if ($id === null) {
            $errors[] = 'Invalid list ID.';
        }

        $lists = $this->listIds($boardId);
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

    /**
     * Get member Ids
     * @param null $id
     * @return array|bool
     */
    private function memberIds($id = null)
    {
        if ($id === null) {
            return false;
        }

        $boards = $this->boardIds();
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
     * Validate board creation
     * @param null $name
     * @return bool|\Illuminate\Http\JsonResponse
     */
    private function validateCreateBoard($name = null)
    {
        $allBoards = $this->boardIds();
        foreach ($allBoards as $key => $value) {
            if ($value === $name) {
                return $this->jsonError(['Board name already exists.'], 400);
            }
        }

        return false;
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
        $boards = $this->boardIds();
        if (!key_exists($boardId, $boards)) {
            $errors[] = 'Invalid board ID.';
        } else {
            $lists = $this->listIds($boardId);
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
        $boardslist = $this->boardIds();
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
        $membersList = [];
        $members = $trello->boards()->members()->all($boardId);
        foreach ($members as $member) {
            $membersList[] = $member['id'];
        }

        //check if members exists on board
        if (!in_array($memberId, $membersList)) {
            $errors[] = 'Invalid Member ID.';
        }

        if (count($errors) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Validate Due Date
     * @param $date
     * @param $boardId
     * @param $ticketId
     * @param $errors
     * @return bool|\DateTime
     */
    private function validateSetDueDate($date, $boardId, $ticketId, &$errors)
    {
        if (empty($date)) {
            $errors[] = 'Empty date field.';
        }

        //validate board ID
        $boards = $this->boardIds();
        if (!key_exists($boardId, $boards)) {
            $errors[] = 'Invalid board ID.';
            return false;
        }

        //validate ticket ID
        $trello  = $this->instance();
        $tickets = [];
        $Alltickets = $trello->boards()->cards()->all($boardId);
        foreach ($Alltickets as $ticket) {
            $tickets[] = $ticket['id'];
        }
        if (!in_array($ticketId, $tickets)) {
            $errors[] = 'Invalid ticket ID.';
        }

        try {
            $datetime = new \DateTime($date);
        } catch (\Exception $e) {
            $errors[] = 'Invalid datetime format.';
        }

        if (count($errors) > 0) {
            return false;
        }

        return $datetime;
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
        $lists  = $this->listIds($id);

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
