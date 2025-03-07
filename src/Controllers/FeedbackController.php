<?php

namespace App\Controllers;

use Model;
use PDOException;
use Psr\Log\LoggerInterface;
use App\Services\ArrayConversionService;
use Slim\Exception\HttpBadRequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ServerRequestInterface as Request;
use ORM;

class FeedbackController extends HomeController
{
    protected $logger;
    private $arrayConversionService;

    public function __construct(LoggerInterface $logger, ArrayConversionService $arrayConversionService)
    {
        $this->logger = $logger;
        $this->arrayConversionService = $arrayConversionService;
    }

    // Add feedback of user
    public function addFeedback(Request $request, Response $response)
    {
        $this->logger->info('Adding feedback');
        try {
            $data = $request->getParsedBody();
            // get user id from request
            $data['user_id'] = $request->getAttribute('user_id');
            // check if feedback is empty
            if (empty($data['feedback'])) {
                throw new HttpBadRequestException($request, "Feedback is required");
            }
            // Create Feedback
            $feedback = Model::factory('Feedback')->create();
            $feedback->user_id = $data['user_id'];
            $feedback->message = $data['feedback'];
            $feedback->created_at = date('Y-m-d H:i:s');
            $feedback->save();
            $this->logger->info('Feedback added successfully');
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Feedback added successfully',
                'data' => $this->arrayConversionService->convertToArray($feedback)
            ], 201);
        } catch (PDOException $e) {
            $this->logger->error("Database error occurred while adding feedback", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to add feedback", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
    // Get user feedback
    public function getFeedback(Request $request, Response $response)
    {
        $this->logger->info('Fetching feedback');
        try {
            // fetch user feedback with admin reply
            $feedbackList = Model::factory('Feedback')
                ->table_alias('f')
                ->select('f.*')
                ->select_expr('fr.message', 'adminReply')
                ->left_outer_join('feedback_replies', ['f.id', '=', 'fr.feedback_id'], 'fr')
                ->where('user_id', $request->getAttribute('user_id'))
                ->find_many();
            $this->logger->info('Feedback fetched successfully');
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Feedback fetched successfully',
                'data' => $this->arrayConversionService->convertCollectionToArray($feedbackList)
            ]);
        } catch (PDOException $e) {
            return $this->response($response, [
                'status' => 'error',
                'message' => 'Error fetching feedback',
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch feedback", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
    // Get all feedbacks for admin 
    public function getFeedbacks(Request $request, Response $response)
    {
        $this->logger->info('Fetching feedback');
        try {
            $feedbackList = Model::factory('Feedback')
                ->table_alias('f')
                ->select('f.*')
                ->select_expr('fr.message', 'adminReply')
                ->left_outer_join('feedback_replies', ['f.id', '=', 'fr.feedback_id'], 'fr')
                ->find_many();
            $this->logger->info('Feedback fetched successfully');
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Feedback fetched successfully',
                'data' => $this->arrayConversionService->convertCollectionToArray($feedbackList)
            ]);
        } catch (PDOException $e) {
            return $this->response($response, [
                'status' => 'error',
                'message' => 'Error fetching feedback',
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch feedback", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
    // Admin feedback response
    public function addReply(Request $request, Response $response)
    {
        $this->logger->info('Adding reply');
        try {
            $data = $request->getParsedBody();
            $data['admin_id'] = $request->getAttribute('user_id');
            if (empty($data['feedback_id']) || empty($data['reply'])) {
                throw new HttpBadRequestException($request, "Feedback ID and reply are required");
            }
            // Create Reply
            $reply = Model::factory('FeedbackReplies')->create();
            $reply->feedback_id = $data['feedback_id'];
            $reply->admin_id = $data['admin_id'];
            $reply->message = $data['reply'];
            $reply->created_at = date('Y-m-d H:i:s');
            $reply->save();
            $this->logger->info('Reply added successfully');
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Reply added successfully',
                'data' => $this->arrayConversionService->convertToArray($reply)
            ], 201);
        } catch (PDOException $e) {
            $this->logger->error("Database error occurred while adding reply", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Failed to add reply", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
}