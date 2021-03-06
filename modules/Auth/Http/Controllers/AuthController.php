<?php
namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Nwidart\Modules\Facades\Module;
use App\Helpers\Http\Api\C3po;

use Modules\User\Repositories\UserRepository as User;
use Modules\Auth\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    /**
     *
     * @var object
     */
    private $entity;

    /**
     * The name of the entity we're refering to in the Controller
     *
     * @var string
     */
    private $entityName;

    /**
     * The module in which the Controller is
     *
     * @var object
     */
    private $module;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->middleware('auth:api', ['except' => ['register', 'login']]);
        $this->entity = $user;
        $this->entityName = 'user';
        $this->module = Module::find('Auth');
    }

    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     *
     * @return Response
     */
    public function register(RegisterRequest $request)
    {
        $result = $this->entity->createOrUpdate($request);

        $statusCode = 201;
        $message = C3po::prepareMessage('messages', false, 'user_registered', $this->module->getName());
        $data = C3po::prepareData($result, $this->entityName);

        // Send response
        return C3po::respond($statusCode, $message, $data);
    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        $statusCode = 401;
        $message = trans('auth::messages.unauthorized');

        return response()->json([
            'status_code' => $statusCode,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        $statusCode = 200;
        $message = trans('auth::messages.logout_successful');

        return response()->json([
            'status_code' => $statusCode,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $statusCode = 201;
        $message = trans('auth::messages.token_generated');

        return response()->json([
            'status_code' => $statusCode,
            'message' => $message,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ], $statusCode);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}
