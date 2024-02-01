<?php

use App\Http\Controllers\DatapoolUserController;
use App\Http\Controllers\DbController;
use App\Http\Controllers\DataPoolController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\UppyController;
use App\Http\Controllers\DatafileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::get('/kalhmera', function () {
    //return view('welcome');
    return "xamogela giati sou paei, kalhmera!!!";
});



Route::group(['prefix'=> 'uppy','middleware' => 'validateToken'], function() {
    Route::post('s3',[UppyController::class, 'getUploadParameters']);
    Route::post('/s3-multipart',[UppyController::class, 'initiateUpload']);
    Route::get('/s3-multipart/{id}',[UppyController::class, 'getUploadedParts']);
    Route::get('/s3-multipart/{id}/{partNumber}',[UppyController::class, 'getPartPresignedUrl']);
    Route::post('/s3-multipart/{id}/complete',[UppyController::class, 'completeMultipartUpload']);
    Route::delete('/s3-multipart/{id}/abort',[UppyController::class, 'abortMultipartUpload']);

});

Route::group(['prefix' => 'datapools/public'], function () {
    Route::get('all/get', [DataPoolController::class, 'getPublicDatapools']);
    Route::get('{datapoolId}/get', [DataPoolController::class, 'getDatapoolById']);
    Route::prefix('search')->group(function () {
        Route::post('{index}/histogramData/{variable}', [DataPoolController::class, 'getHistogramData']);
        Route::post('{datapoolId}/documents/{from}/{to}', [DataPoolController::class, 'getDatapoolSearchResults']);
        Route::post('{index}/exportCsv', [DataPoolController::class, 'exportCsv']);
        Route::get('{index}/datapoints', [DataPoolController::class, 'getDatapoints']);
    });
});

Route::group(['prefix' => 'user/{identity_provider_id}', 'middleware' => 'validateToken'],  function () {
    Route::post('add', [UserController::class,'makeUser']);

    Route::prefix('datapools')->group(function () {
        Route::post('create', [DataPoolController::class,'createDatapool']);
        Route::get('all/get', [DataPoolController::class,'getUserDatapools']);
        Route::get('private/get', [DataPoolController::class,'getUserPrivateDatapools']);
        Route::get('pinned/get', [DataPoolController::class,'getPinnedDatapools']);

        Route::prefix('search')->group(function () {
            Route::post('{index}/histogramData/{variable}', [DataPoolController::class, 'getPrivateDatapoolHistogramData']);
            Route::post('{datapoolId}/documents/{from}/{to}', [DataPoolController::class, 'getPrivateDatapoolSearchResults']);
            Route::post('{index}/exportCsv', [DataPoolController::class, 'privateDatapoolExportCsv']);
            Route::get('{index}/datapoints', [DataPoolController::class, 'getPrivateDatapoolsDatapoints']);
        });

        Route::prefix('{datapoolId}')->group(function () {
            Route::get('get', [DataPoolController::class, 'getPrivateDatapoolById']);
            Route::post('search', [DataPoolController::class, 'getDatapoolSearchResults']);
//            Route::patch('publish', [DataPoolController::class, 'publishDatapool']);
            Route::patch('lock', [DataPoolController::class, 'unpublishDatapool']);
            Route::patch('rename', [DataPoolController::class, 'renameDatapool']);
            Route::post('pin', [DataPoolController::class, 'pinDatapool']);
            Route::post('unpin', [DataPoolController::class, 'unpinDatapool']);
            Route::post('edit/metadata', [DataPoolController::class, 'editDatapoolMetadata']);
            Route::post('publish', [DataPoolController::class, 'addDatapoolMetadataAndPublish']);
            Route::delete('delete', [DataPoolController::class, 'deleteDatapoolById']);
            Route::get('codebook/download', [DataPoolController::class, 'getCurrentCodebookDownloadUrl']);

            Route::prefix('versions')->group(function(){
                Route::post('codebook/template/get',[DatafileController::class,'generateCodebook']);
                Route::post('codebook/validate', [DatafileController::class,'validateCodebook']);
                Route::post('generate',[DatafileController::class,'generateVersion']);
            });

            Route::prefix('users')->group(function () {
                Route::get('all/get', [DatapoolUserController::class, 'getDatapoolUsers']);
                Route::get('all/get-inverse', [DatapoolUserController::class, 'getUsersNotInDatapool']);
                Route::post('add', [DatapoolUserController::class, 'addUsersToDatapool']);
                Route::delete('{datapoolUserId}/delete', [DatapoolUserController::class, 'deleteUserFromDatapool']);
                Route::post('{datapoolUserId}/role/{roleId}/update', [DatapoolUserController::class, 'updateUserRole']);
            });

            Route::prefix('datafiles')->group(function () {
                Route::post('add', [DatafileController::class, 'addFilesToDatapool']);
                Route::get('all/get', [DatafileController::class, 'getDatapoolDatafiles']);
                Route::get('all/get-inverse', [DatafileController::class, 'getDatapoolDatafilesInverse']);
            });

            Route::prefix('apis')->group(function(){
                Route::get('create',[ApiController::class,'createApi']);
                Route::delete('{api_id}/delete',[ApiController::class,'deleteApi']);
                Route::get('all/get',[ApiController::class,'getAllDatapoolApis']);
                Route::get('get',[ApiController::class,'getUserDatapoolApi']);
                Route::get('{apiId}/get', [ApiController::class, 'getAPISecret']);
            });
        });


    });

    Route::prefix('apis')->group(function(){
        Route::get('all/get',[ApiController::class,'getAllUserApis']);
    });

    Route::prefix('datafiles')->group(function () {
        Route::get('all/get', [DatafileController::class,'getUserDatafiles']);
        Route::post('confirmUpload', [DatafileController::class, 'addDataFiles']);
        Route::prefix('{datafileId}')->group(function () {
            Route::get('datapools/get', [DatafileController::class, 'getDatafileDatapools']);
            Route::get('presigned/get', [DatafileController::class, 'getPresignedUrl']);
            Route::delete('delete', [DatafileController::class, 'deleteDatafile']);
        });
    });

    Route::prefix('tools')->group(function() {
        Route::post('crop', [DatafileController::class, 'transformCrop']);
        Route::post('datacleaner/{type}',[DatafileController::class,'cleanSurveyData']);

    });
});
