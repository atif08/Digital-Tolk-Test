<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Http\Requests;
use App\Models\Distance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repository\BookingRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Class BookingController
 * @package App\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected BookingRepository $repository;


    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct( BookingRepository $bookingRepository ) {
        $this->repository = $bookingRepository;
    }


    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function index( Request $request ) {
        $response = [];

        if ( $user_id = $request->get( 'user_id' ) ) {

            $response = $this->repository->getUsersJobs( $user_id );

        }
        elseif ( $request->__authenticatedUser->user_type == config( 'digital_tolk.admin_role_id' ) || $request->__authenticatedUser->user_type == config( 'digital_tolk.superadmin_role_id' ) ) {
            $response = $this->repository->getAll( $request );
        }

        return response( $response );
    }


    /**
     * @param $id
     * @return Application|Response|ResponseFactory
     */
    public function show( $id ) {
        $job = $this->repository->with( 'translatorJobRel.user' )->find( $id );

        return response( $job );
    }


    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function store( Request $request ) {
        $response = $this->repository->store( $request->__authenticatedUser, $request->all() );

        return response( $response );

    }


    /**
     * @param $id
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function update( $id, Request $request ) {
        [ $data, $cuser ] = $this->getDataAndAuthUser( $request );
        $response = $this->repository->updateJob( $id, \Arr::except( $data, [ '_token', 'submit' ] ), $cuser );

        return response( $response );
    }


    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function immediateJobEmail( Request $request ) {
        $data     = $request->all();
        $response = $this->repository->storeJobEmail( $data );

        return response( $response );
    }


    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory|null
     */
    public function getHistory( Request $request ) {
        if ( $user_id = $request->get( 'user_id' ) ) {

            $response = $this->repository->getUsersJobsHistory( $user_id, $request );

            return response( $response );
        }

        return null;
    }


    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function acceptJob( Request $request ) {
        [ $data, $user ] = $this->getDataAndAuthUser( $request );

        $response = $this->repository->acceptJob( $data, $user );

        return response( $response );
    }


    public function acceptJobWithId( Request $request ) {
        $data = $request->get( 'job_id' );
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId( $data, $user );

        return response( $response );
    }


    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function cancelJob( Request $request ) {
        [ $data, $user ] = $this->getDataAndAuthUser( $request );

        $response = $this->repository->cancelJobAjax( $data, $user );

        return response( $response );
    }


    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function endJob( Request $request ) {
        $response = $this->repository->endJob( $request->all() );

        return response( $response );

    }


    public function customerNotCall( Request $request ) {
        $response = $this->repository->customerNotCall( $request->all() );

        return response( $response );

    }


    /**
     * @param Request $request
     * @return Application|Response|ResponseFactory
     */
    public function getPotentialJobs( Request $request ) {
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs( $user );

        return response( $response );
    }


    public function distanceFeed( Request $request ) {
        $data = $request->all();

        if ( $data[ 'flagged' ] == 'true' ) {
            if ( $data[ 'admincomment' ] == '' ) {
                return "Please, add comment";
            }
        }

        return response( 'Record updated!' );
    }


    public function reopen( Request $request ) {
        $data     = $request->all();
        $response = $this->repository->reopen( $data );

        return response( $response );
    }


    public function resendNotifications( Request $request ) {
        $data     = $request->all();
        $job      = $this->repository->find( $data[ 'jobid' ] );
        $job_data = $this->repository->jobToData( $job );
        $this->repository->sendNotificationTranslator( $job, $job_data, '*' );

        return response( [ 'success' => 'Push sent' ] );
    }


    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications( Request $request ) {
        $data = $request->all();
        $job  = $this->repository->find( $data[ 'jobid' ] );

        try {
            $this->repository->sendSMSNotificationToTranslator( $job );

            return response( [ 'success' => 'SMS sent' ] );
        }
        catch ( \Exception $e ) {
            return response( [ 'success' => $e->getMessage() ] );
        }
    }


    /**
     * @param Request $request
     * @return array
     */
    public function getDataAndAuthUser( Request $request ): array {
        return [ $request->all(), $request->__authenticatedUser ];
    }

}
