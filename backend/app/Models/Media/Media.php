<?php

namespace App\Models\Media;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model {
    
    use SoftDeletes, HasFactory;

    protected $table = 'media';

    protected $fillable = [ 'unique_name', 'thumb_size', 'medium_size', 'base_url', 'folder_path', 'original_name', 'title', 'extension', 'size', 'alt_tag', 'batch_no', 'description', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = [ 'updated_at', 'updated_by', 'deleted_by', 'deleted_at'];

    public static function getMedia($mediaId = null){

        $keys = [ 'id', 'unique_name', 'thumb_size', 'medium_size', 'base_url', 'original_name', 'title', 'extension', 'size', 'alt_tag', 'folder_path'];
        return Media::select($keys)->find($mediaId);
    }
}
