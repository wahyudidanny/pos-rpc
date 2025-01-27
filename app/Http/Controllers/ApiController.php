<?php

namespace App\Http\Controllers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;

class ApiController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/register",
     * operationId="Register",
     * tags={"Register"},
     * summary="User Register",
     * description="User Register here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="User Register",
     *        example = "User Register",
     *       value = {
     *           "name":"DW",
     *           "email":"testingvalue@gmail.com",
     *           "password":"111111"
     *         },)),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name","email","password"},
     *               @OA\Property(property="name", type="text"),
     *               @OA\Property(property="email", type="text"),
     *               @OA\Property(property="password", type="password"),
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Register Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Register Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function register(Request $request)
    {
        //Validate data
        $data = $request->only('name', 'email', 'password', 'role');
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|max:50',
            'role' => 'required|string',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is valid, create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt($request->password),
            'isDeleted' => 0,
        ]);

        //User created, return success response
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     * path="/api/login",
     * operationId="Login Username",
     * tags={"Login Username"},
     * summary="Login",
     * description="Login RPC here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(* @OA\Examples(
     *        summary="Login User",
     *        example = "Login User",
     *       value = {
     *           "email":"yolo@gmail.com",
     *           "password":"111111"
     *         },)),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email", "password"},
     *               @OA\Property(property="email", type="text"),
     *               @OA\Property(property="password", type="password")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credential
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is validated
        //Create token
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 400);

            }
        } catch (JWTException $e) {
            return $credentials;
            return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
            ], 500);
        }

        //Token created, return with success response and jwt token

        $users = DB::SELECT('select a.id
                            ,a.name
                            ,a.email
                            ,a.email_verified_at
                            ,a.role as roleId
                            ,b.roleName as role
                            from users a 
                            inner join users_role b on b.id=a.role
                            where email= ?',
                            [$request->input('email')]);
        
    $data = DB::table('tableaccess')
        ->join('menulist', 'menulist.id', '=', 'tableaccess.menulistId')
        ->join('users_role', 'users_role.id', '=', 'tableaccess.roleId')
        ->join('tableroleaccess', 'tableroleaccess.id', '=', 'tableaccess.roleAccessId')
        ->join('accesslimit', 'accesslimit.id', '=', 'tableaccess.accessLimitId')
        ->select('menulist.id',
                 'menulist.menuName',
                 'menulist.isActive',
                 'users_role.roleName',
                 'tableroleaccess.accessName',
                 'accesslimit.timeLimit', )
       ->where([['tableaccess.roleId', '=', $users[0]->roleId], ])
       ->get();

       return response()->json([
             'success' => true,
             'token' => $token,
             'userId' =>$users[0]->id,
             'userName' => $users[0]->name,
             'userEmail' => $users[0]->email,
             'userVerifiedAt' => $users[0]->email_verified_at,
             'role' => $users[0]->role,
             'menuLevel' => $data
        ]);


        // $users = DB::SELECT('select id
        //                            ,name
        //                            ,email
        //                            ,email_verified_at
        //                            ,role
        //                            from users
        //                            where email= ?',
        //                            [$request->input('email')]);

        // $checkdataLocation = DB::table('location')
        //                 ->select('locationName')
        //                 ->where('locationName', '=', $request->locationName)
        //                 ->first();

        // return response()->json([
        //      'success' => true,
        //      'token' => $token,
        //      'userId' =>$users[0]->id,
        //      'userName' => $users[0]->name,
        //      'userEmail' => $users[0]->email,
        //      'userVerifiedAt' => $users[0]->email_verified_at,
        //      'role' => $users[0]->role
        // ]);

        //     $Data = DB::table('users')
        //     ->select('name', 'email')
        //     ->where('email', '=',$request->input('email'))
        //     ->get();

        //    return response()->json([
        //          'success' => true,
        //          'token' => $token,
        //          'userId' =>$users[0]->id,
        //          'userName' => $users[0]->name,
        //     ]);

    }

/**
 * @OA\Post(
 * path="/api/logout",
 * operationId="Logout Username",
 * tags={"Logout Username"},
 * summary="Logout",
 * description="Logout RPC",
 *     @OA\RequestBody(
 *         @OA\JsonContent(* @OA\Examples(
 *        summary="Logout User",
 *        example = "Logout User",
 *       value = {
 *           "token":"",
 *         },)),
 *         @OA\MediaType(
 *            mediaType="multipart/form-data",
 *            @OA\Schema(
 *               type="object",
 *               required={"token"},
 *               @OA\Property(property="token", type="text"),
 *            ),
 *        ),
 *    ),
 *      @OA\Response(
 *          response=201,
 *          description="Login Successfully",
 *          @OA\JsonContent()
 *       ),
 *      @OA\Response(
 *          response=200,
 *          description="Login Successfully",
 *          @OA\JsonContent()
 *       ),
 *      @OA\Response(
 *          response=422,
 *          description="Unprocessable Entity",
 *          @OA\JsonContent()
 *       ),
 *      @OA\Response(response=400, description="Bad request"),
 *      @OA\Response(response=404, description="Resource Not Found"),
 *      security={{ "apiAuth": {} }}
 * )
 */

    public function logout(Request $request)
    {
        //valid credential
        $validator = Validator::make($request->only('token'), [
            'token' => 'required',
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is validated, do logout
        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User has been logged out',
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_user(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $user = JWTAuth::authenticate($request->token);

        return response()->json(['user' => $user]);
    }
}
