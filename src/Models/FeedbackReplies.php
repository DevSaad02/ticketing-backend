<?php
namespace App\Models;
use Model;
/**
 * Table name for Paris ORM
 * @var string
 * @property int $id
 * @property int $feedback_id
 * @property int $admin_id
 * @property string $message
 * @property datetime $created_at
 */

class FeedbackReplies extends Model
{
     // Table name as a string
     public static $_table = 'feedback_replies';


     // Primary key column
     public static $_id_column = 'id';
}
