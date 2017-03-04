<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

//
use App\Lib\Videojs\VideojsPlay;
use App\Lib\GetLinkVideo\GetLinkVideo;

use App\FilmRelate;
use App\FilmDetail;
use App\FilmList;
use App\FilmTrailer;
use App\FilmEpisode;
use App\PhimHayConfig;
use App\FilmUserTick;
use App\FilmUserWatch;
use App\FilmCommentLocal;
use App\FilmActor;
use App\FilmDirector;
use App\FilmPerson;
use App\FilmJob;
use App\FilmPersonJob;
use App\FilmCountry;
use App\FilmDetailCountry;
use App\FilmType;
use App\FilmDetailType;
use App\Lib\FilmProcess\FilmProcess;
use App\Lib\CaptchaImages\CaptchaSessionDownloadFilm;
use App\Lib\SessionTimeouts\SessionDownloadFilm;
use App\Lib\CheckLinks\HttpResponseCode;
use Input;
use Auth;
use Schema;
use LRedis;

class FilmController extends Controller {

	public function getTest(){
		// $payload = ['data' => ['_channel' => 'film-comment-2s', 'comment' => 'content'
		// ],
		// 	'event' => 'messages.new'
		// 	];
		// $redis = LRedis::connection();
  //       $redis->publish('film-comment-2', json_encode($payload));
        return 'ok';
		// Schema::table('film_details', function($table) {
		//     //
		//     $table->char('film_kind', 10)->default('truyen')->after('film_category');
		// });
		// Schema::table('film_lists', function($table) {
		//     //
		//     $table->char('film_category', 2)->default('le')->after('film_name_vn');
		// });
		// $film_detail = FilmDetail::all();
		// foreach ($film_detail as $data) {
		// 	if ($data->film_category == 'hhle' || $data->film_category == 'hhbo') {
		// 		$data->film_kind = 'hoat-hinh';
		// 		$data->save();
		// 	}		
		// 	if($data->film_category == 'hhbo' || $data->film_category == 'bo'){
		// 		$film_list = FilmList::find($data->id);
		// 		$film_list->film_category = 'bo';
		// 		$film_list->save();
		// 	}
		// }
		//nam7, nu 8
		// $arr = [87, 88, 91];
		// $film_person = FilmPerson::whereIn('id', $arr)->get();
		// foreach ($film_person as $data) {
		// 	$job = new FilmPersonJob();
		// 	$job->film_person_id = $data->id;
		// 	// $job->film_job_id = 8;
		// 	$job->film_job_id = 7;
		// 	$job->save();
		// }
	}
	//check link
	public function getCheckLink($film_id){
		$film_successes = [];
		$film_errors = [];
		$film_list = FilmList::find($film_id);
		//fb
		if(count($film_list) == 1){
			//
			$film_detail = FilmDetail::find($film_id);
			//check image small
			$check_link = new HttpResponseCode($film_list->film_thumbnail_small);
			if($check_link->checkHttpResponseCode200()){
				//ok
				$temp_content = 'Thumbnail Small: '.$check_link->getStatusCode().':'.$check_link->getStatusCodeName();
				array_push($film_successes, $temp_content);
			}else{
				$temp_content = 'Thumbnail Small: '.$check_link->getStatusCode().':'.$check_link->getStatusCodeName();
				array_push($film_errors, $temp_content);
			}
			$check_link = new HttpResponseCode($film_detail->film_thumbnail_big);
			if($check_link->checkHttpResponseCode200()){
				//ok
				$temp_content = 'Thumbnail Big: '.$check_link->getStatusCode().':'.$check_link->getStatusCodeName();
				array_push($film_successes, $temp_content);
			}else{
				$temp_content = 'Thumbnail Big: '.$check_link->getStatusCode().':'.$check_link->getStatusCodeName();
				array_push($film_errors, $temp_content);
			}
			$check_link = new HttpResponseCode($film_detail->film_poster_video);
			if($check_link->checkHttpResponseCode200()){
				//ok
				$temp_content = 'Poster Video: '.$check_link->getStatusCode().':'.$check_link->getStatusCodeName();
				array_push($film_successes, $temp_content);
			}else{
				$temp_content = 'Thumbnail Video: '.$check_link->getStatusCode().':'.$check_link->getStatusCodeName();
				array_push($film_errors, $temp_content);
			}
			return redirect()->route('admin.film.getShow', $film_list->id)->with(['film_successes' => $film_successes, 'film_errors' => $film_errors]);
		}
		// var_dump($film_successes);
		// var_dump($film_errors);
	}
	public function getFilmInfo($film_dir, $film_id){
		$film_id = (int)$film_id;
		// echo '<br>'.$film_dir;
		// die();
		//find
		// $film_list = FilmList::where('id', $film_id)->where('film_dir_name', $film_dir)->first();
		$film_list = FilmList::find($film_id);
		//fb
		if(count($film_list) == 1 && $film_list->film_dir_name == $film_dir){
			//get info
			$film_detail = FilmDetail::find($film_id);
			//get trailer
			$film_trailer = FilmTrailer::find($film_id);
			//film episdoe id
			$film_episode = FilmEpisode::where('film_id', $film_id)->select('id')->orderBy('id', 'ASC')->take(1)->get();
			//film relate
			$relate_max = 12;
			$film_relates = null;
			$film_relate_adds = null;
			$film_detail_type = FilmDetailType::where('film_id', $film_id)->get();
			$data_type = [];
			foreach ($film_detail_type as $data) {
				array_push($data_type, $data->type_id);
			}
			//random type
			$type_random = 5;
			if(count($data_type) > 0){
				$type_random = $data_type[random_int(0, count($data_type)-1)];
			}			
			if($film_detail->film_relate_id == 1){
				
				if(count($film_detail_type) > 0){
					
					$film_relates = FilmDetail::where('id', '!=', $film_id)
					->whereHas('filmDetailType', function($query) use($type_random){
						$query->where('type_id', $type_random);
					})->select('id')->orderByRaw('RAND()')->take($relate_max)->with('filmList')->get();
					// dump($film_relates);die();
				}else{
					//ko co type
					$film_relates = FilmDetail::where('id', '!=', $film_id)
					->select('id')->orderByRaw('RAND()')->take($relate_max)->with('filmList')->get();
				}		
			}else{
				//maximum 12
				//is relate
				$relate_id = $film_detail->film_relate_id;
				$film_relates = FilmDetail::where('id', '!=', $film_id)
					->whereHas('filmDetailType', function($query) use($relate_id){
						$query->where('film_relate_id', $relate_id);
					})
					->select('id')->orderByRaw('RAND()')->take($relate_max)->with('filmList')->get();
				if(count($film_relates) < $relate_max){
					//random type
					$film_relate_adds = FilmDetail::where('id', '!=', $film_id)
					->whereHas('filmDetailType', function($query) use($type_random){
						$query->where('type_id', $type_random);
					})->select('id')->orderByRaw('RAND()')->take($relate_max)->with('filmList')->get();
				}
			}
			// var_dump($film_episode);die();
			$film_episode_id = 0;
			if(count($film_episode) == 1){
				$film_episode_id = $film_episode[0]->id;
			}
			//get ticked
			$ticked = 0;
			if(Auth::check()){
				$film_user_tick = FilmUserTick::where('film_id', $film_id)->where('user_id', Auth::user()->id)->get();
				if(count($film_user_tick) == 1){
					$ticked = 1;
				}
			}
			//comment
			$film_comment_local_count = FilmCommentLocal::where('film_id', $film_id)->count();
			$film_comment_local_id_last = 0; //ko co comment
			$film_comment_local_last = FilmCommentLocal::where('film_id', $film_id)->first();
			if(count($film_comment_local_last) == 1){
				$film_comment_local_id_last = $film_comment_local_last->id;
			}
			$film_comments = FilmCommentLocal::where('film_id', $film_id)->orderBy('id', 'DESC')->take(10)->with('user')->get();
			//director
			$directors = FilmDirector::where('film_id', $film_id)->with(['filmPerson' => function ($query){
				$query->select('id', 'person_name', 'person_dir_name');
			}])->get();
			$actors = FilmActor::where('film_id', $film_id)->with(['filmPerson' => function ($query){
				$query->select('id', 'person_name', 'person_image', 'person_dir_name');
			}])->get();
			$film_detail_type = FilmDetailType::where('film_id', $film_id)->with(['filmType' => function ($query){
				$query->select('id', 'type_name', 'type_alias');
			}])->get();
			$film_detail_country = FilmDetailCountry::where('film_id', $film_id)->with(['filmCountry' => function ($query){
				$query->select('id', 'country_name', 'country_alias');
			}])->get();
			//channel redis
			$channel_name = 'film-comment-'.$film_id;
			return view('phimhay.film-info', compact('film_list', 'film_detail', 'film_trailer', 'ticked', 'film_episode_id', 'film_relates', 'film_relate_adds', 'film_comments', 'directors', 'actors', 'film_detail_type', 'film_detail_country', 'film_comment_local_count', 'channel_name', 'film_comment_local_id_last'));
		}
		//not found
		return redirect()->route('404');	
	}
	public function getFilmWatch($film_dir, $film_id, $film_episode_id){
		$film_id = (int)$film_id;
		//
		$film_list = FilmList::where('id', $film_id)->where('film_dir_name', $film_dir)->first();
		if(count($film_list) == 1){
			$film_list->film_viewed = $film_list->film_viewed + 1;
			$film_list->save();
			//user watch
			if(Auth::check()){
				$film_user_watch = FilmUserWatch::where('film_id', $film_id)->where('user_id', Auth::user()->id)->first();
				if(count($film_user_watch) == 1){
					//exist --> ++
					$film_user_watch->user_viewed = $film_user_watch->user_viewed + 1;
					$film_user_watch->save();
				}else{
					//add
					$film_user_watch_new = new FilmUserWatch();
					$film_user_watch_new->film_id = $film_id;
					$film_user_watch_new->user_id = Auth::user()->id;
					$film_user_watch_new->user_viewed = 1;
					$film_user_watch_new->save();

				}
			}
			//
			//get ticked
			$ticked = 0;
			if(Auth::check()){
				$film_user_tick = FilmUserTick::where('film_id', $film_id)->where('user_id', Auth::user()->id)->get();
				if(count($film_user_tick) == 1){
					$ticked = 1;
				}
			}
			//get episode
			$film_episode_list = null;
			$film_episode_watch = null;
			if($film_episode_id != 0){
				$film_episode_watch = FilmEpisode::find($film_episode_id);
				$film_episode_list = FilmEpisode::where('film_id', $film_id)->select('id', 'film_link_number', 'film_episode_language', 'film_episode')->get();
				// dump($film_episode_list);
			}
			//
			$film_detail = FilmDetail::find($film_id);
			//film relate
			$relate_max = 12;
			$film_relates = null;
			$film_relate_adds = null;

			if($film_detail->film_relate_id == 1){

				//random type
				$data_type = explode(',', $film_detail->film_type);
				//ran
				$type_random = $data_type[random_int(0, count($data_type)-1)];
				//var_dump($type_random);
				$film_relates = FilmDetail::where('film_type', 'LIKE', '%'.$type_random.'%')->where('id', '!=', $film_id)->take($relate_max)->get();
				//dump($film_relates);
				//die();
			}else{
				//maximum 12
				$film_relates = FilmDetail::where('film_relate_id', $film_detail->film_relate_id)->where('id', '!=', $film_id)->take($relate_max)->get();
				if(count($film_relates) < $relate_max){
					//random type
					$data_type = explode(',', $film_detail->film_type);
					//ran
					$type_random = $data_type[random_int(0, count($data_type)-1)];
					$film_relate_adds = FilmDetail::where('film_type', 'LIKE', '%'.$type_random.'%')->where('id', '!=', $film_id)->where('film_relate_id', '!=', $film_detail->film_relate_id)->take($relate_max - count($film_relates))->get();
				}
			}
			//comment
			$film_comment_local_count = FilmCommentLocal::where('film_id', $film_id)->count();
			$film_comment_local_id_last = 0; //ko co comment
			$film_comment_local_last = FilmCommentLocal::where('film_id', $film_id)->first();
			if(count($film_comment_local_last) == 1){
				$film_comment_local_id_last = $film_comment_local_last->id;
			}
			$film_comments = FilmCommentLocal::where('film_id', $film_id)->orderBy('id', 'DESC')->take(10)->with('user')->get();
			//channel redis
			$channel_name = 'film-comment-'.$film_id;
			return view('phimhay.film-watch', compact('film_list', 'film_detail', 'ticked', 'film_episode_list', 'film_episode_watch', 'film_relates', 'film_relate_adds', 'film_comments', 'film_comment_local_count', 'channel_name', 'film_comment_local_id_last'));
		}
		//not found
		return redirect()->route('404');
	}

	//admin
	public function getAdd(){
		$film_job = FilmJob::all();
		// $film_country = FilmCountry::all();//da co tron service
		return view('admin.film.add', compact('film_job'));
	}
	public function postAdd(Request $request){
		$film_detail = new FilmDetail();
		$film_detail->film_kind = $request->film_kind;
		$film_detail->film_info = $request->film_info;
		$film_detail->film_score_imdb = $request->film_score_imdb;
		$film_detail->film_score_aw = $request->film_score_aw;
		//date
		$day = ($request->film_release_date_day != '') ? $request->film_release_date_day : '??';
		$month = ($request->film_release_date_month != '') ? $request->film_release_date_month : '??';
		$year = ($request->film_release_date_year != '') ? $request->film_release_date_year : '??';
		$film_detail->film_release_date = $day.'-'.$month.'-'.$year;
		$film_detail->film_production_company = $request->film_production_company;
		//phim lien quan moi
		if($request->film_relate_new != ''){
			$film_relate = new FilmRelate();
			$film_relate->film_relate_name = $request->film_relate_new;
			$film_relate->save();
			$film_detail->film_relate_id = $film_relate->id;
		}else if($request->has('film_relate_selected')){
			//co phim lien quan
			$film_detail->film_relate_id = $request->film_relate_selected;
		}else if($request->has('film_relate_no')){
			//ko co phim lien quan
			$film_detail->film_relate_id = 1;
		}
		$film_detail->film_thumbnail_big = $request->film_thumbnail_big;
		$film_detail->film_poster_video = $request->film_poster_video;
		$film_detail->film_key_words = $request->film_key_words;
		$film_detail->save();
		//add country
		if(count($request->film_country_id) > 0){
			$country = [];
			foreach ($request->film_country_id as $key => $value) {
				$country[$key] = ['film_id' => $film_detail->id, 'country_id' => $value];
			}
			$film_country = FilmDetailCountry::insert($country);
		}
		//add type
		if(count($request->film_type_id) > 0){
			$type = [];
			foreach ($request->film_type_id as $key => $value) {
				$type[$key] = ['film_id' => $film_detail->id, 'type_id' => $value];
			}
			$film_detail_type = FilmDetailType::insert($type);
		}
		//add film actor
		if(count($request->actor_id) > 0){
			$actors = [];
			foreach ($request->actor_id as $key => $value) {
				$actors[$key] = ['film_id' => $film_detail->id, 'actor_id' => $value, 'actor_character' => $request->actor_character[$key]];
			}
			$film_actor = FilmActor::insert($actors);
		}
		//add film director
		if(count($request->director_id) > 0){
			$directors = [];
			foreach ($request->director_id as $key => $value) {
				$directors[$key] = ['film_id' => $film_detail->id, 'director_id' => $value];
			}
			$film_director = FilmDirector::insert($directors);
		}
		// die();
		//add film trailer
		$film_trailer = new FilmTrailer();
		$film_trailer->id = $film_detail->id;
		$film_trailer->film_episode_language = $request->film_episode_language;
		$film_trailer->film_src_name = $request->film_src_name;
		$film_trailer->film_src_full = $request->film_src_full;
		//get link
		//getlink
		if($request->film_src_name != 'youtube'){
			//reset src
			$film_trailer->film_src_360p = null;
			$film_trailer->film_src_480p = null;
			$film_trailer->film_src_720p = null;
			$film_trailer->film_src_1080p = null;
			$film_trailer->film_src_2160p = null;
			//gg drive
			$get_link_video = new GetLinkVideo();
			$phim_hay_config = PhimHayConfig::find(1);
			if($phim_hay_config->get_link_local == 1){
				if($request->film_src_name == 'google drive'){
					$get_link_video->getLinkVideoGoogleDrive($request->film_src_full);
					
				}else if($request->film_src_name == 'google photos'){
					$get_link_video->getLinkVideoGooglePhotos($request->film_src_full);	
				}
			}else{
				//remote blogit
				$get_link_video->getLinkVideoByBlogIt($phim_hay_config->get_link_remote_api, $request->film_src_full);
			}
			$film_trailer->film_src_360p = ($get_link_video->getSrc360()) ? $get_link_video->getSrc360() : null;
			$film_trailer->film_src_480p = ($get_link_video->getSrc480()) ? $get_link_video->getSrc480() : null;
			$film_trailer->film_src_720p = ($get_link_video->getSrc720()) ? $get_link_video->getSrc720() : null;
			$film_trailer->film_src_1080p = ($get_link_video->getSrc1080()) ? $get_link_video->getSrc1080() : null;
			$film_trailer->film_src_2160p = ($get_link_video->getSrc2160()) ? $get_link_video->getSrc2160() : null;
		}
		//
		$film_trailer->save();
		// add film list
		$film_list = new FilmList();
		$film_list->id = $film_detail->id;
		$film_list->film_name_en = $request->film_name_en;
		$film_list->film_name_vn = $request->film_name_vn;
		$film_list->film_category = $request->film_category;
		$film_list->film_time = $request->film_time;
		$film_list->film_years = $request->film_release_date_year;
		$film_list->film_quality = '';
		$film_list->film_language = null;
		$film_list->film_thumbnail_small = $request->film_thumbnail_small;
		//
		$film_process = new FilmProcess();
		$film_list->film_dir_name = $film_process->getFilmDirName($request->film_name_vn, $request->film_name_en, $request->film_release_date_year);
		$film_list->film_status = 'Trailer';
		$film_list->save();
		return redirect()->route('admin.film.getShow', $film_detail->id);
	}
	public function getList(){
		$films = FilmDetail::orderBy('id', 'DESC')->with('filmList')->paginate(5);
		$films->setPath(route('admin.film.getList'));
		//dump($film_detail);
		return view('admin.film.list', compact('films'));
	}
	public function getEdit($film_id){
		$film_detail = FilmDetail::find($film_id);
		if(count($film_detail) == 0){
			return redirect()->route('admin.film.getList')->with(['flash_message_error' =>'Get Edit! Không tồn tại film id: '.$film_id]);
		}
		$film_list = FilmList::find($film_id);
		$film_trailer = FilmTrailer::find($film_id);
		$film_job = FilmJob::all();
		$directors = FilmDirector::where('film_id', $film_id)->with(['filmPerson' => function($query){
			$query->select('id', 'person_name');
		}])->get();
		$actors = FilmActor::where('film_id', $film_id)->with(['filmPerson' => function($query){
			$query->select('id', 'person_name');
		}])->get();
		$film_detail_country = FilmDetailCountry::where('film_id', $film_id)->get();
		$film_detail_type = FilmDetailType::where('film_id', $film_id)->get();
		return view('admin.film.edit', compact('film_id', 'film_detail', 'film_list', 'film_trailer','film_job', 'directors', 'actors', 'film_detail_country', 'film_detail_type'));
	}
	public function postEdit($film_id, Request $request){
		$film_detail = FilmDetail::find($film_id);
		if(count($film_detail) == 0){
			return redirect()->route('admin.film.getList')->with(['flash_message_error' =>'Post Edit! Không tồn tại film id: '.$film_id]);
		}
		$film_detail->film_kind = $request->film_kind;
		$film_detail->film_info = $request->film_info;
		$film_detail->film_score_imdb = $request->film_score_imdb;
		$film_detail->film_score_aw = $request->film_score_aw;
		//date
		$day = ($request->film_release_date_day != '') ? $request->film_release_date_day : '??';
		$month = ($request->film_release_date_month != '') ? $request->film_release_date_month : '??';
		$year = ($request->film_release_date_year != '') ? $request->film_release_date_year : '??';
		$film_detail->film_release_date = $day.'-'.$month.'-'.$year;
		$film_detail->film_production_company = $request->film_production_company;
		if(!$request->has('relate')){
			//phim lien quan moi
			if($request->film_relate_new != ''){
				$film_relate = new FilmRelate();
				$film_relate->film_relate_name = $request->film_relate_new;
				$film_relate->save();
				$film_detail->film_relate_id = $film_relate->id;
			}else if($request->has('film_relate_no')){
				//ko co phim lien quan
				$film_detail->film_relate_id = 1;
			}else if($request->has('film_relate_selected')){
				//co phim lien quan
				$film_detail->film_relate_id = $request->film_relate_selected;
			}
		}
		$film_detail->film_thumbnail_big = $request->film_thumbnail_big;
		$film_detail->film_poster_video = $request->film_poster_video;
		$film_detail->film_key_words = $request->film_key_words;
		$film_detail->save();
		//country
		//delete all
		//add
		FilmDetailCountry::where('film_id', $film_id)->delete();
		//crate arr country
		if(count($request->film_country_id) > 0){
			$countries = [];
			foreach ($request->film_country_id as $key => $val) {
				array_push($countries, ['film_id' => $film_id, 'country_id' => $val]);
			}
			//add
			FilmDetailCountry::insert($countries);
		}
		//type
		//delete all
		//add
		FilmDetailType::where('film_id', $film_id)->delete();
		//crate arr type
		if(count($request->film_type_id) > 0){
			$types = [];
			foreach ($request->film_type_id as $key => $val) {
				array_push($types, ['film_id' => $film_id, 'type_id' => $val]);
			}
			//add
			FilmDetailType::insert($types);
		}
		//person
		//xoa all film director --> add lai
		//delete all director from film_director
		FilmDirector::where('film_id', $film_id)->delete();
		//crate arr director
		if(count($request->director_id) > 0){
			$directors = [];
			foreach ($request->director_id as $key) {
				array_push($directors, ['film_id' => $film_id, 'director_id' => $key]);
			}
			//add
			FilmDirector::insert($directors);
		}
		//xoa all film actor --> add lai
		//delete all actor from film_actor
		FilmActor::where('film_id', $film_id)->delete();
		//crate arr director
		if(count($request->actor_id) > 0){
			$actors = [];
			foreach ($request->actor_id as $key => $val) {
				array_push($actors, ['film_id' => $film_id, 'actor_id' => $val, 'actor_character' => $request->actor_character[$key]]);
			}
			//add
			FilmActor::insert($actors);
		}
		// die();
		//add film trailer
		$film_trailer = FilmTrailer::find($film_id);
		$film_trailer->film_episode_language = $request->film_episode_language;
		$film_trailer->film_src_name = $request->film_src_name;
		$film_trailer->film_src_full = $request->film_src_full;
		//get link
		//getlink
		if($request->film_src_name != 'youtube'){
			//reset src
			$film_trailer->film_src_360p = null;
			$film_trailer->film_src_480p = null;
			$film_trailer->film_src_720p = null;
			$film_trailer->film_src_1080p = null;
			$film_trailer->film_src_2160p = null;
			//gg drive
			$get_link_video = new GetLinkVideo();
			$phim_hay_config = PhimHayConfig::find(1);
			if($phim_hay_config->get_link_local == 1){
				if($request->film_src_name == 'google drive'){
					$get_link_video->getLinkVideoGoogleDrive($request->film_src_full);
					
				}else if($request->film_src_name == 'google photos'){
					$get_link_video->getLinkVideoGooglePhotos($request->film_src_full);	
				}
			}else{
				//remote blogit
				$get_link_video->getLinkVideoByBlogIt($phim_hay_config->get_link_remote_api, $request->film_src_full);
			}
			$film_trailer->film_src_360p = ($get_link_video->getSrc360()) ? $get_link_video->getSrc360() : null;
			$film_trailer->film_src_480p = ($get_link_video->getSrc480()) ? $get_link_video->getSrc480() : null;
			$film_trailer->film_src_720p = ($get_link_video->getSrc720()) ? $get_link_video->getSrc720() : null;
			$film_trailer->film_src_1080p = ($get_link_video->getSrc1080()) ? $get_link_video->getSrc1080() : null;
			$film_trailer->film_src_2160p = ($get_link_video->getSrc2160()) ? $get_link_video->getSrc2160() : null;
		}
		//
		$film_trailer->save();
		// add film list
		$film_list = FilmList::find($film_id);
		$film_list->film_name_en = $request->film_name_en;
		$film_list->film_name_vn = $request->film_name_vn;
		$film_list->film_category = $request->film_category;
		$film_list->film_time = $request->film_time;
		$film_list->film_years = $request->film_release_date_year;
		$film_list->film_quality = $request->film_quality;
		if(count($request->film_language) > 0){
			$film_list->film_language = implode(',', $request->film_language);
		}
		$film_list->film_thumbnail_small = $request->film_thumbnail_small;
		//
		$film_process = new FilmProcess();
		$film_list->film_dir_name = $film_process->getFilmDirName($request->film_name_vn, $request->film_name_en, $request->film_release_date_year);
		$film_list->save();
		return redirect()->route('admin.film.getShow', $film_detail->id);
	}
	public function getDelete($film_id){
		$film_detail = FilmDetail::find($film_id);
		if(count($film_detail) == 0){
			return redirect()->route('admin.film.getList')->with(['flash_message_error' =>'Lỗi! Delete: Không tồn tại film id: '.$film_id]);
		}
		$film_detail->delete();
		return redirect()->route('admin.film.getList')->with(['flash_message' =>'Thành công! Delete: Đã Delete Film_Id: '.$film_id]);
	}
	public function getShow($film_id){
		$film_detail = FilmDetail::find($film_id);
		if(count($film_detail) == 0){
			return redirect()->route('admin.film.getList')->with(['flash_message_error' =>'Lỗi! Show! Không tồn tại film_id: '.$film_id]);
		}
		$film_list = FilmList::find($film_id);
		$film_trailer = FilmTrailer::find($film_id);
		$film_episodes = FilmEpisode::where('film_id', $film_id)->paginate(12);
		$film_episodes->setPath(route('admin.film.getShow', $film_id));
		$film_director = FilmDirector::where('film_id', $film_id)->with(['filmPerson' => 
			function ($query){
			$query->select('id', 'person_name', 'person_dir_name');
		}])->get();
		$film_actor = FilmActor::where('film_id', $film_id)->with(['filmPerson' => 
			function ($query){
			$query->select('id', 'person_name', 'person_dir_name');
		}])->get();
		$film_detail_country = FilmDetailCountry::where('film_id', $film_id)->with(['filmCountry' => 
			function ($query){
			$query->select('id', 'country_name');
		}])->get();
		$film_detail_type = FilmDetailType::where('film_id', $film_id)->with(['filmType' => 
			function ($query){
			$query->select('id', 'type_name');
		}])->get();
		return view('admin.film.show', compact('film_detail', 'film_list', 'film_trailer', 'film_id', 'film_episodes', 'film_director', 'film_actor', 'film_detail_country', 'film_detail_type'));
	}
	public function postEditFilmTrailer($film_id, Request $request){
		$film_trailer = FilmTrailer::find($film_id);
		$film_trailer->film_src_name = $request->film_src_name;
		$film_trailer->film_src_full = $request->film_src_full;
		//getlink
		if($request->film_src_name != 'youtube'){
			//reset src
			$film_trailer->film_src_360p = null;
			$film_trailer->film_src_480p = null;
			$film_trailer->film_src_720p = null;
			$film_trailer->film_src_1080p = null;
			$film_trailer->film_src_2160p = null;
			//gg drive
			$get_link_video = new GetLinkVideo();
			$phim_hay_config = PhimHayConfig::find(1);
			if($phim_hay_config->get_link_local == 1){
				if($request->film_src_name == 'google drive'){
					$get_link_video->getLinkVideoGoogleDrive($request->film_src_full);
					
				}else if($request->film_src_name == 'google photos'){
					$get_link_video->getLinkVideoGooglePhotos($request->film_src_full);	
				}
			}else{
				//remote blogit
				$get_link_video->getLinkVideoByBlogIt($phim_hay_config->get_link_remote_api, $request->film_src_full);
			}
			$film_trailer->film_src_360p = ($get_link_video->getSrc360()) ? $get_link_video->getSrc360() : null;
			$film_trailer->film_src_480p = ($get_link_video->getSrc480()) ? $get_link_video->getSrc480() : null;
			$film_trailer->film_src_720p = ($get_link_video->getSrc720()) ? $get_link_video->getSrc720() : null;
			$film_trailer->film_src_1080p = ($get_link_video->getSrc1080()) ? $get_link_video->getSrc1080() : null;
			$film_trailer->film_src_2160p = ($get_link_video->getSrc2160()) ? $get_link_video->getSrc2160() : null;
		}
		$film_trailer->film_episode_language = $request->film_episode_language;
		$film_trailer->save();
		return redirect()->route('admin.film.getShow', $film_id);
	}
	public function postAddFilmEpisode($film_id, Request $request){
		$film_episode = new FilmEpisode();
		$film_episode->film_id = $film_id;
		$film_episode->film_link_number = $request->film_link_number;
		$film_episode->film_episode = $request->film_episode;
		$film_episode->film_src_name = $request->film_src_name;
		$film_episode->film_src_full = $request->film_src_full;
		$film_episode->film_episode_language = $request->film_episode_language;
		$film_episode->film_episode_quality = $request->film_episode_quality;
		$film_episode->film_src_remote = $request->film_src_remote;
		//
		//getlink, youtube ko can getlink
		if($request->film_src_name != 'youtube'){
			//reset src
			$film_episode->film_src_360p = null;
			$film_episode->film_src_480p = null;
			$film_episode->film_src_720p = null;
			$film_episode->film_src_1080p = null;
			$film_episode->film_src_2160p = null;
			//gg drive
			$get_link_video = new GetLinkVideo();
			$phim_hay_config = PhimHayConfig::find(1);
			//
			if($phim_hay_config->get_link_local == 1){
				if($request->film_src_name == 'google drive'){
					$get_link_video->getLinkVideoGoogleDrive($request->film_src_full);
					
				}else if($request->film_src_name == 'google photos'){
					$get_link_video->getLinkVideoGooglePhotos($request->film_src_full);	
				}
			}else{
				//remote blogit
				$get_link_video->getLinkVideoByBlogIt($phim_hay_config->get_link_remote_api, $request->film_src_full);
			}
			$film_episode->film_src_360p = ($get_link_video->getSrc360()) ? $get_link_video->getSrc360() : null;
			$film_episode->film_src_480p = ($get_link_video->getSrc480()) ? $get_link_video->getSrc480() : null;
			$film_episode->film_src_720p = ($get_link_video->getSrc720()) ? $get_link_video->getSrc720() : null;
			$film_episode->film_src_1080p = ($get_link_video->getSrc1080()) ? $get_link_video->getSrc1080() : null;
			$film_episode->film_src_2160p = ($get_link_video->getSrc2160()) ? $get_link_video->getSrc2160() : null;
		}
		$film_episode->save();
		//change -- status
		$film_process = new FilmProcess();
		$film_list = FilmList::find($film_id);
		$film_list->film_quality = $film_process->xulyAddFilmQuality($film_list->film_quality, $request->film_episode_quality);
		//
		if($film_list->film_category == 'le'){
			//le -> xu ly quality
			//change status
			$film_list->film_status = $film_process->xulyGetFilmQuality($film_list->film_quality);
		}else{
			if((int)$request->film_link_number == 1){
				//bo
				$film_list->film_status = 'Tập '.$film_episode->film_episode.'/'.$film_list->film_time.' '.$film_process->xulyGetFilmQuality($film_list->film_quality);
			}
		}
		//set language
		$film_list->film_language = $film_process->xulyAddFilmLanguage($film_list->film_language, $request->film_episode_language);
		$film_list->save();
		return redirect()->route('admin.film.getShow', $film_id);
	}
	public function getEditFilmEpisode($film_id, $id, Request $request){
		$film_episode = FilmEpisode::find($id);
		$film_list = FilmList::find($film_id);
		return view('admin.film.edit-film-episode', compact('film_episode', 'film_list'));
	}
	//test lai
	public function postEditFilmEpisode($film_id, $id, Request $request){
		$film_episode = FilmEpisode::find($id);
		$film_episode->film_src_name = $request->film_src_name;
		$film_episode->film_src_full = $request->film_src_full;
		$film_episode->film_link_number = $request->film_link_number;
		$film_episode->film_episode = $request->film_episode;
		$film_episode->film_episode_language = $request->film_episode_language;
		$film_episode->film_episode_quality = $request->film_episode_quality;
		$film_episode->film_src_remote = $request->film_src_remote;
		//
		//getlink, youtube ko can getlink
		if($request->film_src_name != 'youtube'){
			//reset src
			$film_episode->film_src_360p = null;
			$film_episode->film_src_480p = null;
			$film_episode->film_src_720p = null;
			$film_episode->film_src_1080p = null;
			$film_episode->film_src_2160p = null;
			//gg drive
			$get_link_video = new GetLinkVideo();
			$phim_hay_config = PhimHayConfig::find(1);
			//
			if($phim_hay_config->get_link_local == 1){
				if($request->film_src_name == 'google drive'){
					$get_link_video->getLinkVideoGoogleDrive($request->film_src_full);
					
				}else if($request->film_src_name == 'google photos'){
					$get_link_video->getLinkVideoGooglePhotos($request->film_src_full);	
				}
			}else{
				//remote blogit
				$get_link_video->getLinkVideoByBlogIt($phim_hay_config->get_link_remote_api, $request->film_src_full);
			}
			$film_episode->film_src_360p = ($get_link_video->getSrc360()) ? $get_link_video->getSrc360() : null;
			$film_episode->film_src_480p = ($get_link_video->getSrc480()) ? $get_link_video->getSrc480() : null;
			$film_episode->film_src_720p = ($get_link_video->getSrc720()) ? $get_link_video->getSrc720() : null;
			$film_episode->film_src_1080p = ($get_link_video->getSrc1080()) ? $get_link_video->getSrc1080() : null;
			$film_episode->film_src_2160p = ($get_link_video->getSrc2160()) ? $get_link_video->getSrc2160() : null;
		}
		$film_episode->save();
		//change -- status
		$film_process = new FilmProcess();
		$film_list = FilmList::find($film_id);
		$film_list->film_quality = $film_process->xulyAddFilmQuality($film_list->film_quality, $request->film_episode_quality);
		//
		if($film_list->film_category == 'le'){
			//le -> xu ly quality
			//change status
			$film_list->film_status = $film_process->xulyGetFilmQuality($film_list->film_quality);
		}else{
			//bo
			if((int)$request->film_link_number == 1){
				$film_list->film_status = 'Tập '.$film_episode->film_episode.'/'.$film_list->film_time.' '.$film_process->xulyGetFilmQuality($film_list->film_quality);
			}
		}
		//set language
		$film_list->film_language = $film_process->xulyAddFilmLanguage($film_list->film_language, $request->film_episode_language);
		$film_list->save();
		return redirect()->route('admin.film.getShow', $film_id);
	}
	public function getDeleteFilmEpisode($film_id, $id){
		$film_episode = FilmEpisode::find($id);
		$film_list = FilmList::find($film_id);
		if(count($film_episode) == 0 && count($film_episode) == 0){
			return redirect()->route('admin.film.getList')->withErrors('Lỗi! Xóa Episode không thành công, không có episode_id '.$id.' ở film_id '.$film_id.' để xóa');
		}
		$film_episode->delete();
		return redirect()->route('admin.film.getShow', $film_id)->with(['flash_message' => 'Thành công! Đã xóa episode_id '.$id.' ở film_id '.$film_id]);
	}
	//phim auto or search
	//film?country=abc&type=hanh-dong&year=2999
	public function getSearch(){
		$name = Input::get('name');
		$category = Input::get('category');
		$kind = Input::get('kind');
		$type = Input::get('type');
		$country = Input::get('country');
		$year = Input::get('year');
		// echo $category. '-'.$type.'-'.$country.'-'.$year;
		// die();
		//var_dump($type);
		//
		//case 1: tat ca deu null -> phim new
		$films = null;
		//ko name
		if(empty($name)){
			if($category == null && $type == null && $country == null && $year == null && $kind == null){
				//get id episode
				// $films = FilmDetail::distinct()
				// ->whereIn('id', function($query2){
				// 	$query2->from('film_episodes')
				// 	->select('id')->distinct();
				// })
				// ->orderBy('id', 'DESC')->with('filmList')->paginate(25);
				$films = FilmDetail::orderBy('id', 'DESC')->
				with('filmList')->paginate(25);

			}
			else{
				//is year
				if(!empty($year)){		
					//is year
					//truoc2010
					if($year == 'truoc2010'){
						$truoc2010 = 2010;
						//is country
						if(!empty($country)){
							//is type
							if(!empty($type)){
								//is kind
								if(!empty($kind)){
									//is category
									if(!empty($category)){
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010, $category){
											$q->where('film_years', '<', $truoc2010)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//ko category
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010){
											$q->where('film_years', '<', $truoc2010);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
								}else{
									//ko kind
									// ->whereHas('filmList', function($q2) use($category){
									// 		$q2->where('film_category', $category);
									// 	})
									//is category
									if(!empty($category)){
										$films = FilmDetail::
										whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010, $category){
											$q->where('film_years', '<', $truoc2010)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//ko category
										$films = FilmDetail::
										whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010){
											$q->where('film_years', '<', $truoc2010);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
									
								}
							}else{
								//ko type
								//is kind
								if(!empty($kind)){
									//is category
									if(!empty($category)){
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010, $category){
											$q->where('film_years', '<', $truoc2010)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//is year
										//is country
										//ko type
										//is kind
										//ko category
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010){
											$q->where('film_years', '<', $truoc2010);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
								}else{
									//ko type, ko kind
									//is category
									if(!empty($category)){
										$films = FilmDetail::
										whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010, $category){
											$q->where('film_years', '<', $truoc2010)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//is year, is country, ko type ko kind ko cate
										$films = FilmDetail::
										whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010){
											$q->where('film_years', '<', $truoc2010);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
									
								}	
							}
						} /*endif(!empty($country))*/
						//ko co country
						else{
							//is type
							if(!empty($type)){
								//is kind
								if(!empty($kind)){
									//is category
									if(!empty($category)){
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010, $category){
											$q->where('film_years', '<', $truoc2010)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//ko category
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010){
											$q->where('film_years', '<', $truoc2010);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
								}else{
									//ko kind
									//is category
									if(!empty($category)){
										$films = FilmDetail::
										whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010, $category){
											$q->where('film_years', '<', $truoc2010)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//ko category
										$films = FilmDetail::
										whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmList', function($q) use($truoc2010){
											$q->where('film_years', '<', $truoc2010);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
									
								}
							}else{
								//ko type
								//is kind
								if(!empty($kind)){
									//is category
									if(!empty($category)){
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmList', function($q) use($truoc2010, $category){
											$q->where('film_years', '<', $truoc2010)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//is year
										//ko country
										//ko type
										//is kind
										//ko category
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmList', function($q) use($truoc2010){
											$q->where('film_years', '<', $truoc2010);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
								}else{
									//ko type, ko kind
									//is category
									if(!empty($category)){
										$films = FilmDetail::
										whereHas('filmList', function($q) use($truoc2010, $category){
											$q->where('film_years', '<', $truoc2010)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//is year, ko country, ko type ko kind ko cate
										$films = FilmDetail::
										whereHas('filmList', function($q) use($truoc2010){
											$q->where('film_years', '<', $truoc2010);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
									
								}	
							}
							
						}
					}else{
						//is year binh thuong:
						//is country
						if(!empty($country)){
							//is type
							if(!empty($type)){
								//is kind
								if(!empty($kind)){
									//is category
									if(!empty($category)){
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($year, $category){
											$q->where('film_years', $year)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//ko category
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($year){
											$q->where('film_years', $year);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
								}else{
									//ko kind
									// ->whereHas('filmList', function($q2) use($category){
									// 		$q2->where('film_category', $category);
									// 	})
									//is category
									if(!empty($category)){
										$films = FilmDetail::
										whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($year, $category){
											$q->where('film_years', $year)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//ko category
										$films = FilmDetail::
										whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($year){
											$q->where('film_years', $year);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
									
								}
							}else{
								//ko type
								//is kind
								if(!empty($kind)){
									//is category
									if(!empty($category)){
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($year, $category){
											$q->where('film_years', $year)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//is year
										//is country
										//ko type
										//is kind
										//ko category
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($year){
											$q->where('film_years', $year);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
								}else{
									//ko type, ko kind
									//is category
									if(!empty($category)){
										$films = FilmDetail::
										whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($year, $category){
											$q->where('film_years', $year)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//is year, is country, ko type ko kind ko cate
										$films = FilmDetail::
										whereHas('filmDetailCountry', function($query) use($country){
												$query->whereHas('filmCountry', function($query2) use($country){
													$query2->where('country_alias', $country);
												});
											})
										->whereHas('filmList', function($q) use($year){
											$q->where('film_years', $year);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
									
								}	
							}
						} /*endif(!empty($country))*/
						//ko co country
						else{
							//is type
							if(!empty($type)){
								//is kind
								if(!empty($kind)){
									//is category
									if(!empty($category)){
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmList', function($q) use($year, $category){
											$q->where('film_years', $year)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//ko category
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmList', function($q) use($year){
											$q->where('film_years', $year);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
								}else{
									//ko kind
									//is category
									if(!empty($category)){
										$films = FilmDetail::
										whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmList', function($q) use($year, $category){
											$q->where('film_years', $year)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//ko category
										$films = FilmDetail::
										whereHas('filmDetailType', function($query) use($type){
												$query->whereHas('filmType', function($query2) use($type){
													$query2->where('type_alias', $type);
												});
											})
										->whereHas('filmList', function($q) use($year){
											$q->where('film_years', $year);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
									
								}
							}else{
								//ko type
								//is kind
								if(!empty($kind)){
									//is category
									if(!empty($category)){
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmList', function($q) use($year, $category){
											$q->where('film_years', $year)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//is year
										//ko country
										//ko type
										//is kind
										//ko category
										$films = FilmDetail::where('film_kind', $kind)
										->whereHas('filmList', function($q) use($year){
											$q->where('film_years', $year);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
								}else{
									//ko type, ko kind
									//is category
									if(!empty($category)){
										$films = FilmDetail::
										whereHas('filmList', function($q) use($year, $category){
											$q->where('film_years', $year)->where('film_category', $category);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}else{
										//is year, ko country, ko type ko kind ko cate
										$films = FilmDetail::
										whereHas('filmList', function($q) use($year){
											$q->where('film_years', $year);
										})
										->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
									}
									
								}	
							}
							
						}
					}
				} /*endif !empty year*/
				else{
					//ko year
					//is country
					//is country
					if(!empty($country)){
						//is type
						if(!empty($type)){
							//is kind
							if(!empty($kind)){
								//is category
								if(!empty($category)){
									$films = FilmDetail::where('film_kind', $kind)
									->whereHas('filmDetailType', function($query) use($type){
											$query->whereHas('filmType', function($query2) use($type){
												$query2->where('type_alias', $type);
											});
										})
									->whereHas('filmDetailCountry', function($query) use($country){
											$query->whereHas('filmCountry', function($query2) use($country){
												$query2->where('country_alias', $country);
											});
										})
									->whereHas('filmList', function($q) use($category){
										$q->where('film_category', $category);
									})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}else{
									//ko category
									$films = FilmDetail::where('film_kind', $kind)
									->whereHas('filmDetailType', function($query) use($type){
											$query->whereHas('filmType', function($query2) use($type){
												$query2->where('type_alias', $type);
											});
										})
									->whereHas('filmDetailCountry', function($query) use($country){
											$query->whereHas('filmCountry', function($query2) use($country){
												$query2->where('country_alias', $country);
											});
										})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}
							}else{
								//ko kind
								//is category
								if(!empty($category)){
									$films = FilmDetail::
									whereHas('filmDetailType', function($query) use($type){
											$query->whereHas('filmType', function($query2) use($type){
												$query2->where('type_alias', $type);
											});
										})
									->whereHas('filmDetailCountry', function($query) use($country){
											$query->whereHas('filmCountry', function($query2) use($country){
												$query2->where('country_alias', $country);
											});
										})
									->whereHas('filmList', function($q) use($category){
										$q->where('film_category', $category);
									})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}else{
									//ko category
									$films = FilmDetail::
									whereHas('filmDetailType', function($query) use($type){
											$query->whereHas('filmType', function($query2) use($type){
												$query2->where('type_alias', $type);
											});
										})
									->whereHas('filmDetailCountry', function($query) use($country){
											$query->whereHas('filmCountry', function($query2) use($country){
												$query2->where('country_alias', $country);
											});
										})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}
								
							}
						}else{
							//ko type
							//is kind
							if(!empty($kind)){
								//is category
								if(!empty($category)){
									$films = FilmDetail::where('film_kind', $kind)
									->whereHas('filmDetailCountry', function($query) use($country){
											$query->whereHas('filmCountry', function($query2) use($country){
												$query2->where('country_alias', $country);
											});
										})
									->whereHas('filmList', function($q) use($category){
										$q->where('film_category', $category);
									})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}else{
									//ko year
									//is country
									//ko type
									//is kind
									//ko category
									$films = FilmDetail::where('film_kind', $kind)
									->whereHas('filmDetailCountry', function($query) use($country){
											$query->whereHas('filmCountry', function($query2) use($country){
												$query2->where('country_alias', $country);
											});
										})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}
							}else{
								//ko type, ko kind
								//is category
								if(!empty($category)){
									$films = FilmDetail::
									whereHas('filmDetailCountry', function($query) use($country){
											$query->whereHas('filmCountry', function($query2) use($country){
												$query2->where('country_alias', $country);
											});
										})
									->whereHas('filmList', function($q) use($category){
										$q->where('film_category', $category);
									})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}else{
									//ko year, is country, ko type ko kind ko cate
									$films = FilmDetail::
									whereHas('filmDetailCountry', function($query) use($country){
											$query->whereHas('filmCountry', function($query2) use($country){
												$query2->where('country_alias', $country);
											});
										})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}
								
							}	
						}
					} /*endif(!empty($country))*/
					//ko co country
					else{
						//is type
						if(!empty($type)){
							//is kind
							if(!empty($kind)){
								//is category
								if(!empty($category)){
									$films = FilmDetail::where('film_kind', $kind)
									->whereHas('filmDetailType', function($query) use($type){
											$query->whereHas('filmType', function($query2) use($type){
												$query2->where('type_alias', $type);
											});
										})
									->whereHas('filmList', function($q) use($category){
										$q->where('film_category', $category);
									})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}else{
									//ko category
									$films = FilmDetail::where('film_kind', $kind)
									->whereHas('filmDetailType', function($query) use($type){
											$query->whereHas('filmType', function($query2) use($type){
												$query2->where('type_alias', $type);
											});
										})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}
							}else{
								//ko kind
								//is category
								if(!empty($category)){
									$films = FilmDetail::
									whereHas('filmDetailType', function($query) use($type){
											$query->whereHas('filmType', function($query2) use($type){
												$query2->where('type_alias', $type);
											});
										})
									->whereHas('filmList', function($q) use($category){
										$q->where('film_category', $category);
									})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}else{
									//ko category
									$films = FilmDetail::
									whereHas('filmDetailType', function($query) use($type){
											$query->whereHas('filmType', function($query2) use($type){
												$query2->where('type_alias', $type);
											});
										})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}
								
							}
						}else{
							//ko type
							//is kind
							if(!empty($kind)){
								//is category
								if(!empty($category)){
									$films = FilmDetail::where('film_kind', $kind)
									->whereHas('filmList', function($q) use($category){
										$q->where('film_category', $category);
									})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}else{
									//ko year
									//ko country
									//ko type
									//is kind
									//ko category
									$films = FilmDetail::where('film_kind', $kind)
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}
							}else{
								//ko type, ko kind
								//is category
								if(!empty($category)){
									$films = FilmDetail::
									whereHas('filmList', function($q) use($category){
										$q->where('film_category', $category);
									})
									->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}else{
									//ko year, ko country, ko type ko kind ko cate
									$films = FilmDetail::
									select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
								}
								
							}	
						}
						
					}

				} /*end else ko year */
			}
		}else{
			//is name
			//change - to space
			$name = preg_replace('/[-]/', ' ', $name);
			// var_dump($name);
			$films = FilmDetail::whereIn('id', function($query3) use ($name){
				$query3->from('film_lists')
				->select('id')->where('film_name_vn', 'LIKE', '%'.$name.'%')->orWhere('film_name_en', 'LIKE', '%'.$name.'%')->get();
			})
					->select('id')->orderBy('id', 'DESC')->with('filmList')->paginate(25);
		}
		$films->setPath(route('film.getSearch'));
		// dump($films);
		return view('phimhay.film-search', compact('films', 'category', 'kind', 'type', 'country', 'year', 'name'));
	}
	//
	public function getFilmDownloadCaptcha($film_dir, $film_id, Request $request){
		$film_list =  FilmList::find($film_id);
		//check film
		if(count($film_list) == 0){
			return redirect()->route('404');
		}
		else if(count($film_list) == 1 && $film_list->film_dir_name != $film_dir){
			return redirect()->route('404');
		}
		//exist session  ->film-download
		$session_download_film = new SessionDownloadFilm($film_id);
		if($session_download_film->checkSessionUses()){
			return redirect()->route('film.getFilmDownload', [$film_dir, $film_id]);
		}
		//captcha auto
		return view('phimhay.film-download-captcha', compact('film_list'));
	}
	public function getFilmDownload($film_dir, $film_id, Request $request){
		$film_list =  FilmList::find($film_id);
		//check film
		if(count($film_list) == 0){
			return redirect()->route('404');
		}
		else if(count($film_list) == 1 && $film_list->film_dir_name != $film_dir){
			return redirect()->route('404');
		}
		$session_download_film = new SessionDownloadFilm($film_id);
		//check
		if($session_download_film->checkSessionUses()){
			// ton tai
			$film_episode = FilmEpisode::where('film_id', $film_id)->where('film_link_number', 1)->paginate(5);
			$film_episode->setPath(route('film.getFilmDownload', [$film_dir, $film_id]));
			return view('phimhay.film-download', compact('film_episode', 'film_list'));
			
		}
		//
		if($request->method('post') && $request->captcha_download_film != ''){
			$captcha_download_film = new CaptchaSessionDownloadFilm();
			$captcha_download_film->createCheckCaptchaSessionUses($request->captcha_download_film);
			//check captcha
			if($captcha_download_film->checkCaptchaSessionUses()){
				//forget
				$captcha_download_film->forgetCaptchaSessionUses();
				//session film_id
				$session_download_film->forgetSessionUses();
				$session_download_film->createSessionUses();
				//page 10
				$film_episode = FilmEpisode::where('film_id', $film_id)->where('film_link_number', 1)->paginate(20);
				$film_episode->setPath(route('film.getFilmDownload', [$film_dir, $film_id]));
				return view('phimhay.film-download', compact('film_episode', 'film_list'));
			}else{
				return redirect()->back()->withErrors('Sai mã bảo vệ hoặc hết thời hạn!');
			}
		}
		//ko ton tai session
		// return redirect()->route('film.getFilmDownloadCaptcha', [$film_dir, $film_id])->withErrors('Lỗi! Hết thời gian timeout!');
		
	}
	//
	public function getSearchAdmin(){
		return view('admin.film.search');
	}
}
