<?php
namespace App\Models;
use Model;
/**
 * Table name for Paris ORM
 * @var string
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone
 */

class User extends Model
{
     // Table name as a string
     public static $_table = 'users';


     // Primary key column
     public static $_id_column = 'id';
}
