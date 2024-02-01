<?php

namespace App\Services;

use App\Models\API;
use App\Models\Datafile;
use App\Models\Datapool;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserPinnedDatapool;
use App\Models\Job;
use App\Models\Views\AllUserDatafilesDistinctView;
use App\Models\Views\ApiUserDatapoolView;
use App\Models\Views\DatapoolDatafileView;
use App\Models\Views\AllUserDatafilesView;
use App\Models\Views\UserDatapoolRoleApiView;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;
use function PHPUnit\Framework\throwException;

class DBService
{
    public function addUser($request, $identity_provider_id) {
        $user_exists = User::where('identity_provider_id', $identity_provider_id)->first();
        if (is_null($user_exists)) {
            $body = json_decode($request->getContent(), false);
            try {
                DB::beginTransaction();

                $user = new User();
                $user->identity_provider_id = $identity_provider_id;
                $user->name = $body->name;
                $user->surname = $body->surname;
                $user->email = $body->email;
                $user->save();

                DB::commit();

                return response("User added successfully", 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response($e->getMessage(), 400);
            }
        } else {
            return response("User Exists", 200);
        }
    }

    public function pinDatapool($userId, $datapoolId) {
        try {
            DB::beginTransaction();

            $user = User::where('identity_provider_id', $userId)->first();
            if (!is_null($user)) {
                $pin_exists = UserPinnedDatapool::where('user_id', $user->user_id)->where('datapool_id', $datapoolId)->first();
                if (is_null($pin_exists)) {
                    $dp = Datapool::find($datapoolId);
                    $user->pinnedDatapools()->attach($dp->datapool_id);
                    DB::commit();
                    return response("Datapool pinned successfully", 200);
                } else {
                    throw new Exception("Pin Exists", 409);
//                    return response("Pin Exists", 409);
                }
            } else {
                throw new Exception("User does not exist", 400);
            }
        }
        catch (Exception $e) {
            Log::info('Pin Error ', [$e->getMessage()]);
            DB::rollBack();
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function unpinDatapool($userId, $datapoolId) {
        try {
            DB::beginTransaction();

            $user = User::where('identity_provider_id', $userId)->first();
            if (!is_null($user)) {
                $pin_exists = UserPinnedDatapool::where('user_id', $user->user_id)->where('datapool_id', $datapoolId)->first();
                if (!is_null($pin_exists)) {
                    $pin_exists->delete();
                    DB::commit();
                    return response("Pin deleted successfully", 200);
                } else {
                    throw new Exception("Pin does not exist", 409);
                }
            } else {
                throw new Exception("User does not exist", 400);
            }
        }
        catch (Exception $e) {
            Log::info('Unpin Error ', [$e->getMessage()]);
            DB::rollBack();
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function getPinnedDatapools($userId) {
        try {
            $pinned = UserPinnedDatapool::where('user_id', $userId)->get();
            Log::info("Pinned Datapools: ", [$pinned]);
            return response()->json($pinned);
        }
        catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function  createDatapool($userId, $datapoolId, $name) {
        try {
            DB::beginTransaction();

            $user = User::where('identity_provider_id', $userId)->first();
            if (!is_null($user)) {
                $dp = new Datapool();
                $dp->mongo_id = $datapoolId;
                $dp->alias = UtilitiesService::transformAlias($name);
                $dp->name = $name;
                $dp->status = 'private';
                $dp->save();
                Log::info("Created datapool: ", [$dp]);

                $user->datapools()->attach($dp->datapool_id, ["role_id" => 1]);
                DB::commit();
                return response("Datapool created successfully", 200);
            } else {
                throw new Exception("User does not exist");
            }
        } catch (Exception $e) {
            Log::info('Datapool creation error: ', [$e->getMessage()]);
            DB::rollBack();
            return response($e->getMessage(), 400);
        }
    }

    public function getDatapoolUsers($datapoolId) {
        try {
            $view = UserDatapoolRoleApiView::where('mongo_id', $datapoolId)->get();

            return response()->json($view);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function getUsersNotInDatapool($datapoolId) {
        try {
            $dp = Datapool::where('mongo_id', $datapoolId)->first();
            $users = User::whereDoesntHave('datapools', function (Builder $query) use ($dp) {
                $query->where('User_Datapool.datapool_id', '=', $dp->datapool_id);
            })->get();
            Log::info('Getting users not in datapool...');
            Log::info('User array: ',[$users]);
            Log::info('Datapool ID: ', [$datapoolId]);
            return response()->json($users);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function addUsersToDatapool($datapoolId, $users) {
        try {
            DB::beginTransaction();
            $dp = Datapool::where('mongo_id', $datapoolId)->first();
            if (!is_null($dp)) {
                Log::info('Adding users...');
                Log::info('User array: ', [$users]);
                Log::info('Datapool ID: ', [$datapoolId]);

                foreach ($users as $u) {
                    $user = User::find($u['user_id']);
                    if (!is_null($user)) {
                        $user->datapools()->attach($dp->datapool_id, ['role_id' => 0]);
                    } else {
                        throw new Exception("User " . $u['user_id'] . " not found");
                    }
                }

                DB::commit();
                return response('Users added successfully', 200);

            } else {
                throw new Exception("Datapool " . $datapoolId .  " not found");
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::info("Could not add users to datapool: ". $e->getMessage());
            return response($e->getMessage(), 400);
        }
    }

    public function deleteUserFromDatapool($datapoolId, $datapoolUserId) {
        try {
            DB::beginTransaction();
            $dp = Datapool::where('mongo_id', $datapoolId)->first();
            if (!is_null($dp)) {
                Log::info('Removing user from datapool', [$datapoolUserId, $datapoolId]);

                $user = User::where('identity_provider_id', $datapoolUserId)->first();
                if (!is_null($user)) {
                    $result = $user->datapools()->detach($dp->datapool_id);
                    Log::info("Datapool:", [$dp]);
                    Log::info("User:", [$user]);
                    Log::info("Detach result: ", [$result]);
                    if ($result == 1) {
                        DB::commit();
                        return response('User removed successfully', 200);
                    } else if ($result == 0) {
                        throw new Exception("Detach failed. User likely not associated with this datapool.");
                    }
                } else {
                    throw new Exception("User " . $datapoolUserId . " not found");
                }
            } else {
                throw new Exception("Datapool " . $datapoolId . " not found");
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            return response($e->getMessage(), 400);
        }
    }

    public function updateUserRole($datapoolId, $datapoolUserId, $roleId) {
        try {
            DB::beginTransaction();
            $dp = Datapool::where('mongo_id', $datapoolId)->first();
            if (!is_null($dp)) {
                Log::info('Updating user role in datapool: ', [$datapoolUserId, $datapoolId, $roleId]);
                $user = User::where('identity_provider_id', $datapoolUserId)->first();
                if (!is_null($user)) {
                    $relationship_exists = $user->datapools->contains($dp->datapool_id);

                    if ($relationship_exists) {
                        $user->datapools()->updateExistingPivot($dp->datapool_id, ['role_id' => $roleId]);
                        DB::commit();
                        return response('User role updated', 200);
                    } else {
                        throw new Exception("Update failed. User not associated with this datapool.");
                    }
                } else {
                    throw new Exception("User " . $datapoolUserId . " not found");
                }

            } else {
                throw new Exception("Datapool " . $datapoolId . " not found");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 400);
        }
    }

    public function getUserDatafiles($userId) {
        try {
            Log::info("User ID: " . $userId);
            $view = AllUserDatafilesDistinctView::where('identity_provider_id', $userId)->get();

            Log::info("User datafiles: ", [$view]);
            return response()->json($view);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

//    public function addFilesToDatapool($userId, $datapoolId, $datafiles) {
//        try {
//            DB::beginTransaction();
//            $dp = Datapool::where('mongo_id', $datapoolId)->first();
//            if (!is_null($dp)) {
//                Log::info('Adding files...');
//                Log::info('File array: ', [$datafiles]);
//                Log::info('Datapool ID: ', [$datapoolId]);
//
//                foreach ($datafiles as $df) {
//                    $datafile = Datafile::find($df['datafile_id']);
//                    if (!is_null($datafile)) {
//                        $result = $datafile->datapools()->attach($dp->datapool_id, ['order' => 1]);
//                        if ($result == 0) {
//                            throw new Exception("Attach unsuccessful");
//                        }
//                    } else {
//                        throw new Exception("Datafile " . $df['datafile_id'] . " not found");
//                    }
//                }
//                DB::commit();
//                return response('Datafiles added successfully', 200);
//            } else {
//                throw new Exception("Datapool " . $datapoolId . " not found");
//            }
//        } catch (Exception $e) {
//            DB::rollBack();
//            return response($e->getMessage(), 400);
//        }
//    }

    public function addDataFiles($userId, $fileKeyArray){
        try {
            DB::beginTransaction();
            $user = User::where('identity_provider_id', $userId)->first();
            if (!is_null($user)) {
                foreach ($fileKeyArray as $fileKey) {
                    $fileKeyParts = UtilitiesService::parseS3FileKey($fileKey);

                    $datafile = new Datafile();
                    $datafile->key = $fileKey;
                    $datafile->creation_time = UtilitiesService::parseTimestamp($fileKeyParts['timestamp']);
                    $datafile->filename = $fileKeyParts['filename'];
                    $datafile->save();

                    Log::info("Added datafile: " . $datafile);

                    $user->datafiles()->attach($datafile->datafile_id);
                }
                DB::commit();
                return response("Datafile created successfully", 200);
            }  else {
                throw new Exception("User " . $userId . " not found");
            }

        } catch (Exception $e){
            DB::rollBack();
            return response($e->getMessage(), 400);
        }
    }

    public function getDatapoolFiles($datapoolId) {
        try {
            $view = DatapoolDatafileView::where([
                ['mongo_id', '=', $datapoolId],
                ['completed', '=', 1]
                ])->get();
            Log::info($datapoolId);
            Log::info($view);
            return response()->json($view);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function getFileDatapools($datafileId) {
        try {
            $view = DatapoolDatafileView::where([
                ['datafile_id', '=', $datafileId],
                ['completed', '=', 1]
            ])->get();

            return response()->json($view);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function getFilesNotInDatapool($userId, $datapoolId) {
        try {
            $dp = Datapool::where('mongo_id', $datapoolId)->first();
            $user = User::where('identity_provider_id', $userId)->first();

            $datafiles = Datafile::where(function($subQuery) use ($user, $dp) {
                $subQuery->whereDoesntHave('datapools', function ( $query ) use ($dp) {
                    $query->where('Datapool_Datafile.datapool_id', $dp->datapool_id );
                })->whereHas('users', function ($query) use ($user) {
                    $query->where('User_Datafile.user_id', $user->user_id);
                });
            })->get();

            Log::info($datafiles);

            Log::info('Getting datafiles not in datapool...');
            Log::info('Datafiles array: ',[$datafiles]);
            Log::info('Datapool ID: ', [$datapoolId]);
            return response()->json($datafiles);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function getDatafileDownloadUrl($datafileId) {
        try {
            $df = Datafile::find($datafileId);
            $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($df->key, now()->addMinutes(10));
            return response()->json(["url" => $temporarySignedUrl],  200);
        } catch (Exception $e){
            return response($e->getMessage(), 400);

        }
    }

    public function deleteDatafile($datafileId) {
        try {
            $df = Datafile::find($datafileId);
            $result = Storage::disk('s3')->delete($df->key);
            if ($result) {
                $df->users()->detach();
                $df->delete();
            }
        } catch (Exception $e){
            return response($e->getMessage(), 400);

        }

    }

    public function getCurrentCodebookPresignedUrl($datapoolId) {
        try {
            $dp = Datapool::with(['datafiles' => function ($query) {
                $query->where('current', '=', 1);
            }])
                ->where('mongo_id', $datapoolId)
                ->first();
            $temporarySignedUrl = Storage::disk('s3')->temporaryUrl($dp->datafiles[0]->pivot->codebook, now()->addMinutes(10));
            return response()->json(["url" => $temporarySignedUrl],  200);
        } catch (Exception $e){
            return response($e->getMessage(), 400);

        }
    }

    public function softDeleteDatapool($datapoolId) {
        try {
            Datapool::where('mongo_id', $datapoolId)
                ->update(['deleted' => 1]);

            Log::info('Soft Deleting Datapool ID: ', [$datapoolId]);
            return response('Datapool deleted', 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function renameDatapool($datapoolId, $name) {
        try {
            Datapool::where('mongo_id', $datapoolId)
                ->update(['name' => $name]);

            Log::info('Renaming datapool: ', [$datapoolId, $name]);
            return response('Datapool renamed', 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    private function getUserDatapools($userId)
    {
        try {
            $view = UserDatapoolRoleApiView::with('tags')->where([
                ['identity_provider_id', '=', $userId],
                ['deleted', '=', 0]
            ])->get();
            Log::info($userId);
            Log::info($view);
            return response()->json(['datapools' => $view, 'filters' => $this->getUniqueTags($view)]);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    private function getUserPrivateDatapools($userId)
    {
        try {
            $view = UserDatapoolRoleApiView::with('tags')->where([
                ['identity_provider_id', '=', $userId],
                ['deleted', '=', 0],
                ['status', '=', 'private']
            ])->get();
            Log::info($userId);
            Log::info($view);
            return response()->json(['datapools' => $view, 'filters' => $this->getUniqueTags($view)]);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    private function getPublicDatapools()
    {
        try {
            $view = Datapool::with('tags')->where([
                ['status', '=', 'public'],
                ['deleted', '=', 0]
            ])->get();
            Log::info($view);
            return response()->json(['datapools' => $view, 'filters' => $this->getUniqueTags($view)]);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    private function getUniqueTags($dpArray)
    {
        $crops = array();
        $regions = array();
        $countries = array();
        foreach ($dpArray as $dp) {
            foreach($dp['tags'] as $tag) {
                if ($tag->type === 'country') {
                    if (!in_array($tag->tag, $countries)) {
                        $countries[] = $tag->tag;
                    }
                } else if ($tag->type === 'region') {
                    if (!in_array($tag->tag, $regions)) {
                        $regions[] = $tag->tag;
                    }
                } else if ($tag->type === 'crop') {
                    if (!in_array($tag->tag, $crops)) {
                        $crops[] = $tag->tag;
                    }
                }
            }
        }
        return ['countries' => $countries, 'regions' => $regions, 'crops' => $crops];
    }

    public function getDatapools($type, $userId) {
        if ($type === 'user') {
            return $this->getUserDatapools($userId);
        } else if ($type === 'public') {
            return $this->getPublicDatapools();
        } else if ($type === 'private') {
            return $this->getUserPrivateDatapools($userId);
        }
    }

    public function changeDatapoolPublishStatus($datapoolId, $status) {
        try {
            $result = Datapool::where('mongo_id', $datapoolId)
                ->update(['status' => $status]);

            Log::info('Publishing Datapool: ', [$datapoolId]);
            return response('Datapool status changed to ' . $status, 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function editDatapoolMetadata($description, $citation, $license, $datapoolId) {
        try {
            $result = Datapool::where('mongo_id', $datapoolId)
                ->update(['description' => $description, 'citation' => $citation, 'license' => $license ]);

            Log::info('Changed datapool metadata', [$datapoolId]);
            return response('Datapool metadata changed ', 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function generateVersion($datapoolId, Request $request) {
        try {
            $dp = Datapool::where('mongo_id', $datapoolId)->first();
            $dp->datafiles()->attach($request->input('dataFileId'),
                [
                    'version' => $request->input('version'),
                    'codebook' => $request->input('codebook'),
                    'codebook_template' => $request->input('codebookTemplate'),
                ]);
            return response("Version inserted successfully", 200);
        } catch (Exception $e){
            return response($e->getMessage(), 400);
        }
    }

    public function insertJob($userId, $dpId, $dfId, $jobResponse) {
        try {
            $mongoId = json_decode($jobResponse->getContent(), true)['id'];
            $user = User::where('identity_provider_id', $userId)->first();
            $dp = Datapool::where('mongo_id', $dpId)->first();

            $job = new Job();
            $job->mongo_id = $mongoId;
            $job->datapool_id = $dp->datapool_id;
            $job->datafile_id = $dfId;
            $user->jobs()->save($job);

            return response('Job saved to SQL table', 200);
        } catch (Exception $e){
            return response($e->getMessage(), 400);
        }
    }

    public function updateJobResults($jobsArray, $userId) {
        foreach ($jobsArray as $job) {
            if ($job['status'] === 'error') {
                $this->deleteFailedIndexJob($job);
                return null;
            } else if ($job['status'] === 'complete-with-errors') {
                $this->deleteFailedIndexJob($job);
                return null;
            } else if ($job['status'] === 'complete') {
                $jobObj = Job::where('mongo_id', $job['id'])->first();
//                $dp = Datapool::find($jobObj->datapool_id);

                $dp = $this->updateDbJobResults($jobObj);
                return $dp;
            }
        }
        return null;
    }

    private function updateDbJobResults($job) {
        try {
            $dp = Datapool::find($job->datapool_id);

            $ids = $dp->datafiles()->allRelatedIds();
            foreach($ids as $id) {
                $dp->datafiles()->updateExistingPivot($id, ['current' => 0]);
            }

            $dp->datafiles()->updateExistingPivot($job->datafile_id, ['completed' => 1, 'current' => 1]);
            $job->delete();

            $fullDp = Datapool::with(['datafiles' => function ($query) {
                $query->where('current', '=', 1);
            }])
                ->where('datapool_id', $job->datapool_id)
                ->first();

            return $fullDp;
        } catch (Exception $e){
            return response($e->getMessage(), 400);
        }
    }

    public function updateDatapoolMetadata($body, $dp) {
        try {
            Log::info($body['records']);
            Log::info($dp);
//            $datapool = Datapool::find($dp->datapool_id);
//            $datapool->update(['records' => $body['records']]);
            Datapool::where('datapool_id', $dp->datapool_id)
                ->update(['records' => $body['records']]);
            return response('Updated datapool metadata', 200);
        } catch (Exception $e){
            return response($e->getMessage(), 400);
        }
    }

    public function deleteFailedIndexJob($jobParams) {
        try {
            $job = Job::where('mongo_id', $jobParams['id'])->first();

            $dp = Datapool::find($job->datapool_id);
            $dp->datafiles()->detach($job->datafile_id);

            $job->delete();
            return response('Job failed. Deleting version and job.', 200);
        } catch (Exception $e){
            return response($e->getMessage(), 400);
        }
    }

    public function getPendingJobs($userId) {
        try {
            $user = User::where('identity_provider_id', $userId)->first();
            $jobs = Job::where('user_id', $user->user_id)->get();

            return $jobs;
        } catch (Exception $e){
            return $e;
        }
    }

    public function parseTagMetadata($bodyArray,$datapool)
    {
        $typeArray=['countries'=>'country','regions'=>'region','crops'=>'crop'];
        foreach ($typeArray as $type_array=> $sql_type){
            if ($bodyArray[$type_array])
                foreach (($bodyArray[$type_array]) as $tag){
                    Log::info($sql_type);
                    Log::info($tag);

                    $this->insertTag($datapool,$sql_type,$tag);
        }}
    }
    private function insertTag($datapool,$type,$tagValue)
    {

        //gia datapool_id pros8ese  oles tis loipes plhrofories gia tags
        try {

            $tag = new Tag();
            $tag->tag =$tagValue;
            $tag->type =$type;
            $datapool->tags()->save($tag);

            return response("Tag created successfully", 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }

    }

    public function createApi($userId, $datapool_id, $auth0_id) {
        try {
            $user = User::where('identity_provider_id', $userId)->first();
            $datapool = Datapool::where('datapool_id', $datapool_id)->first();

            $api = new API();

            $api->auth_zero_id = $auth0_id;
            $api->deleted = 0;

            $api->user()->associate($user);
            $api->datapool()->associate($datapool);
            $api->save();

            Log::info("Created api: ", [$api]);
            return response("Api created successfully", 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function softDeleteApi( $auth0_id ) {
        try {
            API::where('auth_zero_id', $auth0_id)->update(['deleted' => 1]);

            Log::info('Soft Deleting Api Id: ', [$auth0_id]);
            return response('Api deleted', 200);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function getAllUserApis( $userId ) {
        try {
            $user = User::where('identity_provider_id', $userId)->first();
            $apis = ApiUserDatapoolView::where([
                ['user_id', '=', $user->user_id],
                ['deleted', '=', 0]
            ])->get();
            Log::info($userId);
            Log::info($apis);
            return response()->json(['apis' => $apis]);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function getAllDatapoolApis( $datapool_id ) {
        try {
            $apis = API::where([
                ['datapool_id', '=', $datapool_id],
                ['deleted', '=', 0]
            ])->get();
            Log::info($datapool_id);
            Log::info($apis);
            return response()->json(['apis' => $apis]);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }

    public function getUserDatapoolApi( $userId, $datapool_id ) {
        try {
            $user = User::where('identity_provider_id', $userId)->first();
            $apis = API::where([
                ['datapool_id', '=', $datapool_id],
                ['user_id', '=', $user->user_id],
                ['deleted', '=', 0]
            ])->first();
            Log::info($datapool_id);
            Log::info($apis);
            return response()->json($apis);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }
    }
}
