<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTrait;
use App\PollPlaylist;
use App\PollVoter;

class Poll extends Model
{
  use ModelTrait;

  protected $table = 'polls';
  protected $primaryKey = 'pol_id';
  protected $fillable = ['pol_user', 'pol_title', 'pol_description', 'pol_status', 'pol_votes', 'pol_expiry'];

  public function playlists()
  {
      return $this->hasMany('App\PollPlaylist', 'polp_poll', 'pol_id')->withPlaylist()->orderBy('polp_order', 'asc');
  }

  public function voters()
  {
      return $this->hasMany('App\PollVoter', 'pov_poll', 'pol_id')->select('poll_voters.*')->withUser()->orderBy('pov_id', 'desc');
  }

  public function scopeGetPublicVoters($query)
  {
    return $query->isPublic->voters;
  }

  public function scopeFilterOwner($query, $owner = null)
  {
      if (!is_null($owner)) {
          $query->where('pol_user', '=', $owner);
      }
  }

  public function scopeFilter($query, $filter = [])
  {
      if (array_get($filter, 'pol_title')) {
          $query->where('pol_title', 'like', '%' . $filter['pol_title'] . '%');
      }
      if (array_get($filter, 'pol_user')) {
          $query->where('name', 'like', '%' . $filter['pol_user'] . '%');
      }
  }


  public function scopeFilterActive($query)
  {
      $query->where('pol_status', '=', 'active');
  }

  public function scopeWithOwner($query)
  {
      return $query->join('users', 'id', '=', 'pol_user');
  }

  public function scopeIsPublic($query)
  {
      return $query->where('pol_expiry', '<', 'curdate()');
  }

  public function owner()
  {
      return $this->belongsTo('App\User', 'pol_user');
  }

  public function updateVotes($pol_id)
  {
    $total = PollPlaylist::where('polp_poll', '=', $pol_id)->sum('polp_vote');

    return $this->find($pol_id)->update(['pol_votes' => $total ]);
  }

  public static function boot()
  {
      Poll::saving(function ($post) {
        if(array_get($post, 'pol_expiry')) {
          $post['pol_expiry'] = date('Y-m-d', strtotime($post['pol_expiry']));
        }
      });
  }
}
