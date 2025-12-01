<?php

use App\Http\Controllers\Api\auth\AuthBuyerController;
use App\Http\Controllers\Api\auth\CheckUserExistsController;
use App\Http\Controllers\Api\auth\LoginController;
use App\Http\Controllers\Api\auth\PasswordController;
use App\Http\Controllers\Api\auth\ProfessorLoginController;
use App\Http\Controllers\Api\auth\RegisteredStudentController;
use App\Http\Controllers\Api\auth\RegisteredUserController;
use App\Http\Controllers\Api\auth\SellerLoginController;
use App\Http\Controllers\Api\auth\SmsController;
use App\Http\Controllers\Api\buyer\BuyerController;
use App\Http\Controllers\Api\buyer\DashboardBuyerController;
use App\Http\Controllers\Api\buyer\MyOrderController;
use App\Http\Controllers\Api\classes\PrivetClassController;
use App\Http\Controllers\Api\classes\WebinrController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\LibreryController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Professors\Calender;
use App\Http\Controllers\Api\Professors\ClassPriceController;
use App\Http\Controllers\Api\Professors\ClassReservationController;
use App\Http\Controllers\Api\Professors\ClassTypeController;
use App\Http\Controllers\Api\Professors\GroupClassController;
use App\Http\Controllers\Api\Professors\PlanController;
use App\Http\Controllers\Api\Professors\PrivetClassProfessorController;
use App\Http\Controllers\Api\Professors\ProfessorBook;
use App\Http\Controllers\Api\Professors\ProfessorDashboardController;
use App\Http\Controllers\Api\Professors\ProfessorGoaleController;
use App\Http\Controllers\Api\Professors\ProfessorPanelController;
use App\Http\Controllers\Api\Professors\ProfessorsComments;
use App\Http\Controllers\Api\Professors\ProfessorTeachingInfoController;
use App\Http\Controllers\Api\Professors\ProfessorTimeController;
use App\Http\Controllers\Api\Professors\StudentProfileForProfessorController;
use App\Http\Controllers\Api\Professors\WebinarController;
use App\Http\Controllers\Api\Professors\WorkShopsController;
use App\Http\Controllers\Api\seller\OrderSellerController;
use App\Http\Controllers\Api\seller\sellerCommentController;
use App\Http\Controllers\Api\seller\SellerController;
use App\Http\Controllers\Api\seller\SellerDashboardController;
use App\Http\Controllers\Api\seller\SellerDiscountController;
use App\Http\Controllers\Api\seller\SellerProfileController;
use App\Http\Controllers\Api\seller\SellerWalletController;
use App\Http\Controllers\Api\shop\BanerController;
use App\Http\Controllers\Api\shop\CategoriesController;
use App\Http\Controllers\Api\shop\EducationalProductController;
use App\Http\Controllers\Api\shop\LangugesController;
use App\Http\Controllers\Api\shop\MainPageController;
use App\Http\Controllers\Api\shop\OrderController;
use App\Http\Controllers\Api\shop\ProductController;
use App\Http\Controllers\Api\shop\RegionController;
use App\Http\Controllers\Api\shop\StoryController;
use App\Http\Controllers\Api\Students\classController;
use App\Http\Controllers\Api\Students\DashboardController;
use App\Http\Controllers\Api\Students\DiscountForStudentController;
use App\Http\Controllers\Api\Students\exam\Exam1Controller;
use App\Http\Controllers\Api\Students\exam\ExamController;
use App\Http\Controllers\Api\Students\ExamPayments\ExamPaymentController;
use App\Http\Controllers\Api\Students\FavoriteController;
use App\Http\Controllers\Api\Students\PrivateClass;
use App\Http\Controllers\Api\Students\StudentBookController;
use App\Http\Controllers\Api\Students\StudentCertificateController;
use App\Http\Controllers\Api\Students\StudentPanelController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LibraryCotroller;
use App\Http\Controllers\MassageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WalletController;
use App\Models\Blog;
use App\Models\News;
use App\Models\Professors\GroupClass;
use App\Models\Professors\Webinar;
use App\Models\Professors\WorkShop;
use App\Models\shop\Product;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;


Route::get('/sitemap.xml', function () {
    $sitemap = Sitemap::create();

    // Static pages
    $sitemap->add(Url::create('https://lingomasters.ir/'));
    $sitemap->add(Url::create('https://lingomasters.ir/private-class?language=1'));
    $sitemap->add(Url::create('https://lingomasters.ir/group-class'));
    $sitemap->add(Url::create('https://lingomasters.ir/webinar'));
    $sitemap->add(Url::create('https://lingomasters.ir/workshop'));
    $sitemap->add(Url::create('https://lingomasters.ir/library'));
    $sitemap->add(Url::create('https://lingomasters.ir/contact-us'));
    $sitemap->add(Url::create('https://lingomasters.ir/blogs'));
    $sitemap->add(Url::create('https://lingomasters.ir/news'));
    $sitemap->add(Url::create('https://lingomasters.ir/exams/pay'));

    // Dynamic routes (sample)
    foreach (\App\Models\Professors\Professor::all() as $class) {
        $sitemap->add(Url::create("https://lingomasters.ir/private-class/{$class->id}?id={$class->id}&language={$class->language_id}"));
    }

    foreach (GroupClass::all() as $class) {
        $sitemap->add(Url::create("https://lingomasters.ir/group-class/{$class->id}"));
    }

    foreach (Webinar::all() as $webinar) {
        $sitemap->add(Url::create("https://lingomasters.ir/webinar/{$webinar->id}"));
    }

    foreach (Workshop::all() as $workshop) {
        $sitemap->add(Url::create("https://lingomasters.ir/workshop/{$workshop->id}"));
    }

    foreach (Product::all() as $product) {
        $sitemap->add(Url::create("https://lingomasters.ir/library/{$product->id}"));
    }

    foreach (Blog::all() as $blog) {
        $sitemap->add(Url::create("https://lingomasters.ir/blogs/{$blog->id}"));
    }

    foreach (News::all() as $news) {
        $sitemap->add(Url::create("https://lingomasters.ir/news/{$news->id}"));
    }

    return response($sitemap->render())->header('Content-Type', 'application/xml');
});

Route::prefix('exam')->controller(App\Http\Controllers\Api\Exam\ExamController::class)->group(function () {
    Route::get('', 'exams');
    Route::get('/final', 'final');
    Route::get('/show/{id}', 'exam_show')->middleware('optional.auth');
    Route::post('/comment/{id}', 'comment')->middleware('auth:sanctum');
    Route::get('/comment/{id}', 'showComments');
    Route::post('/like/{id}', 'toggleLike')->middleware('auth:sanctum');
    Route::post('/rate/{id}', 'rate')->middleware('auth:sanctum');


    Route::post('/answer', 'submitAnswers')->middleware('auth:sanctum');
    Route::post('/getPartAnswers', 'getPartAnswers')->middleware('auth:sanctum');
    Route::get('/{id}', 'question')->middleware('auth:sanctum');
    Route::get('/beforeFinishExam/{id}', 'beforeFinishExam')->middleware('auth:sanctum');
    Route::post('/finishExam/{id}', 'finishExam')->middleware('auth:sanctum');

    Route::prefix('placement')->controller(\App\Http\Controllers\Api\Exam\PlacementController::class)->group(function () {
        Route::get('/info', 'info');
        Route::post('/placement', 'placement')->middleware('auth:sanctum');
    });
});

Route::post('/questionnaires', [QuestionnaireController::class, 'store']);
Route::get('/placement', [PlacementController::class, 'index']);
Route::get('/placement/professors', [PlacementController::class, 'professors']);


Route::prefix('exam')->controller(\App\Http\Controllers\Api\Exam\ExamController::class)->group(function () {
    Route::get('/{id}', 'question');
    Route::post('/answer', 'submitAnswers')->middleware('auth:sanctum');
});

Route::get('/orders/{order}/invoice', [OrderSellerController::class, 'downloadInvoice']);
Route::prefix('professor-panel')->controller(ProfessorPanelController::class)->middleware('optional.auth')->name('professor.')->group(function () {
    Route::get('/language', 'showLanguage')->name('language-show');

});
Route::prefix('library')->controller(LibreryController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/GetFilter', 'GetFilter');
    Route::get('/show/{id}', 'show');

});


Route::get('/send-sms', [\App\Http\Controllers\Api\sendMessageController::class, 'sendSms']);


////////////// login and register ////////////
Route::prefix('/register')->controller(RegisteredUserController::class)->name('register.')->group(function () {
    Route::post('/professor-store', 'store')->name('store');
});
Route::post('/professor-login', [ProfessorLoginController::class, 'apiLogin'])->middleware('throttle:login-attempt');
Route::post('/newsletter', [NewsletterController::class, 'post']);



Route::prefix('/register')->controller(RegisteredStudentController::class)->name('register1.')->group(function () {
    Route::post('/student-store', 'store')->name('store');
});
Route::post('/login', [LoginController::class, 'apiLogin'])->middleware('throttle:login-attempt');



Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout']);
Route::post('/check', [CheckUserExistsController::class, 'checkUserExists']);
Route::post('/buyerLogin', [AuthBuyerController::class, 'otpLogin']);
Route::post('/buyerRegister', [AuthBuyerController::class, 'otpRegister']);

Route::post('/loginSeller', [SellerLoginController::class, 'otpLogin']);
Route::post('/registerSeller', [SellerLoginController::class, 'simpleRegister']);
Route::post('/checkSeller', [SellerLoginController::class, 'checkUserExists']);


/////
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/surveys/{type}/{reservation_id}', [SurveyController::class, 'show']);
    Route::post('/surveys/{type}/{reservation_id}', [SurveyController::class, 'store']);
    Route::get('/surveys/professor', [SurveyController::class, 'professorSurveyStats']);
});

Route::prefix('password')->middleware('auth:sanctum')->controller(PasswordController::class)->name('password.')->group(function () {
    Route::post('/change', 'changePassword')->name('change');
});
Route::get('/user-role/{id}', [StoryController::class, 'getUserRole']);

Route::post('send-otp', [SmsController::class, 'sendOtp'])->name('send-otp');
Route::post('/contactUs', [ContactUsController::class, 'store']);
Route::get('/search', [SearchController::class, 'q_search']);
Route::get('/search_all', [SearchController::class, 'search']);
Route::get('/filters', [SearchController::class, 'filters']);


////////////////////////// main //////////////////////////
Route::get('/landing-page', [LandingPageController::class, 'index'])->name('landing-page');
Route::post('/landing-page/consultation', [LandingPageController::class, 'consultation_form'])->name('landing-consultation_form');
Route::get('/landing-page/class', [LandingPageController::class, 'class'])->name('landing-page-classes')->middleware('optional.auth');


Route::prefix('/news')->controller(NewsController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/level_1', 'index1');
    Route::get('/level_2  ', 'index2');
    Route::get('/show/{id}', 'show');

});
Route::prefix('/exams')
    ->controller(Exam1Controller::class)
    ->group(function () {
        Route::get('/user-exams', 'userExams');
    });
Route::get('/seller/{seller}/revenue', [SellerWalletController::class, 'getRevenue']);
Route::get('/x', [ProfessorPanelController::class, 'x']);


    Route::prefix('/exam-payments')->controller(ExamPaymentController::class)->middleware('auth:sanctum')->group(function () {

    Route::post('/', 'store');
    Route::post('/pay/wallet/{id}', 'wallet');
    Route::post('/pay/{id}', 'pay_group_reservation');
    Route::get('/order/{id}', 'showOrderExamPayment');
});
Route::prefix('/exam-payments')->controller(ExamPaymentController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}/info', 'showExamPayment');
});

Route::prefix('blogs')->middleware('optional.auth')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('blogs.index');
    Route::get('/all', [BlogController::class, 'index_a']);
    Route::get('/{blog}', [BlogController::class, 'show'])->name('blogs.show');
});
Route::prefix('blog')->middleware('auth:sanctum')->group(function () {
    Route::post('/like/{id}', [BlogController::class, 'toggleLike']);
});
Route::prefix('/exams')
    ->controller(Exam1Controller::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/comment/{id}', 'showComments');
        Route::post('/like/{id}', 'toggleLike');
        Route::post('/comment/{id}', 'comment');
        Route::post('/answers_part', 'answers_part');
        Route::get('/examDetailLevelSet/{id}', 'examDetailLevelSet');
        Route::get('/{id}/detail', 'examDetail');
        Route::get('/{id}/', 'examQuestions');
        Route::get('/results', 'ExamsResults');
        Route::get('/{id}/result', 'ExamResult');
        Route::post('/pay/{reservationId}', 'payExamReservation');
        Route::get('/zarin_callback', 'zarin_callback');
        Route::post('/test-correction', 'TestCorrection');
        Route::get('/examCheckout/{id}', 'examCheckout');
        Route::post('/pay/wallet/{reservationId}', 'pay_private_reservation_Wallet');
        Route::post('/pay/{reservationId}', 'pay_group_reservation');

    });


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::post('/tickets/{id}', [TicketController::class, 'addMessage']);
    Route::get('/tickets/{id}', [TicketController::class, 'showTicketConversation']);
    Route::get('/user/tickets', [TicketController::class, 'userTickets']);
    Route::post('/tickets/close/{id}', [TicketController::class, 'closeTicket']);
});
Route::prefix('comment')->controller(ProfessorsComments::class)->group(function () {
    Route::get('/text1', 'TextComments1');

});


Route::prefix('blogs')->group(function () {
    Route::get('/main', [BlogController::class, 'main'])->name('blogs.main');
    Route::get('/main', [BlogController::class, 'main'])->name('blogs.main');
    Route::get('/comment/{blog}', [BlogController::class, 'showComments'])->name('blogs.comment');
    Route::post('/comment/{id}', [BlogController::class, 'comment'])->name('blogs.commentStore    ');
});


Route::prefix('professorLibrary')->controller(LibraryCotroller::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/show/{id}', 'show');
});


Route::get('/chat/messages/{receiverId}', [MassageController::class, 'getMessagesWithUser'])->middleware('auth:sanctum');
Route::get('/chat/my-chats', [MassageController::class, 'getMyChats'])->middleware('auth:sanctum');
Route::get('/chat/typing-status', [MassageController::class, 'typing'])->middleware('auth:sanctum');
Route::get('/chat/chat_info/{receiverId}', [MassageController::class, 'chat_info'])->middleware('auth:sanctum');
Route::get('/chat/user_details/{user}', [MassageController::class, 'user_details']);


Route::get('/price/{reservationId}/{session}', [PrivetClassController::class, 'calculatePrivetClassPrice']);
Route::get('/teachers/{id}', [PrivetClassController::class, 'know']);


Route::get('/countries', [CountryController::class, 'getCountries']);


Route::prefix('notification')->controller(NotificationController::class)->name('notif.')->group(function () {
    Route::get('/', 'indexProfessor')->name('indexProfessor')->middleware('auth:sanctum');
//    Route::get('/', 'indexProfessor')->name('indexProfessor')->middleware('auth:sanctum');
});
Route::get('/main-page', [MainPageController::class, 'index']);
Route::post('/exams/like/{id}', [ExamController::class, 'toggleLike'])->middleware('auth:sanctum');

Route::controller(RegionController::class)->group(function () {

    Route::prefix('province')->group(function () {
        Route::get('/', 'index_province')->name('province');
    });

    Route::prefix('city')->group(function () {
        Route::get('/', 'index_city')->name('city');
    });
});
Route::post('/send-message/{receiverId}', [MassageController::class, 'sendMessage'])->middleware('auth:sanctum');


Route::prefix('reservation')->controller(ClassReservationController::class)->name('reservation.')->group(function () {
    Route::get('/{professor}', 'index')->name('index');
    Route::get('/', 'index2')->name('index2');
    Route::post('/store', 'store')->name('store');
});

Route::prefix('educational-product')->controller(EducationalProductController::class)->name('educational-product.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/get-filter', 'GetFilter')->name('get-filter');
    Route::get('/show/{product}', 'show')->name('show');
    Route::post('/store', 'store')->name('store');
});

Route::prefix('banner')->controller(BanerController::class)->name(' baner.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/store', 'store')->name('store');

});
Route::prefix('product')->controller(ProductController::class)->middleware('optional.auth')->name('product.')->group(function () {
    Route::get('/', 'index1')->name('index1');
    Route::get('/off-price', 'index_off')->name('index_off');
    Route::get('/show/{product}', 'show')->name('show');
    Route::get('/seller/{seller_id}', 'sellerProducts');
    Route::get('/seller/info/{id}', 'seller_info');
    Route::get('/get-filter/{slug}', 'GetFilter')->name('filter');
    Route::get('/get-filter', 'GetFilter1')->name('filter1');
    Route::post('/rate/{id}', 'rate');
    Route::post('/comment/{id}', 'comment');
    Route::get('/comment/{id}', 'showComments');
    Route::post('/like/{id}', 'toggleLike');
    Route::get('/q_search', 'q_search');
    Route::get('/search', 'search');

    Route::get('/{slug}', 'index')->name('index');

});
Route::prefix('order')->controller(OrderController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/address', 'address');
    Route::post('/', 'store');
    Route::post('/wallet', 'pay_private_reservation_Wallet');
    Route::post('/checkCart', 'checkCart');
    Route::post('/checkDiscount', 'checkDiscount');
});


Route::prefix('category')->controller(CategoriesController::class)->name('category.')->group(function () {
    Route::get('/', 'index')->name('index');

});

Route::prefix('language')->controller(LangugesController::class)->name('language.')->group(function () {
    Route::get('/', 'index')->name('index');

});
Route::prefix('geo_location')->controller(ProfessorTeachingInfoController::class)->group(function () {
    Route::get('/', 'region');

});


Route::prefix('private-reserve')->controller(PrivetClassController::class)->name('professor.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/show/{professor}', 'show')->name('show')->middleware('optional.auth');
    Route::post('/{id}', 'reserve')->name('reserve')->middleware('auth:sanctum');
    Route::post('/{id}/reserve-placement', 'reserve1')->name('reserve1')->middleware('auth:sanctum');
    Route::get('/calender/{id}', 'calender')->name('calender')->middleware('auth:sanctum');
    Route::get('/get-filter', 'getFilters')->name('getFilters');
    Route::get('/details/{professor}', 'ditiles')->name('ditiles');
    Route::get('/details2/{professor}', 'ditiles2');
    Route::get('/comment/{id}', 'showComments')->name('showComments');
    Route::post('/comment/{groupClassId}', 'comment')->name('comment')->middleware('auth:sanctum');
    Route::get('/info/{reservationId}', 'info22');
    Route::post('/rate/{groupClassId}', 'rate')->middleware('auth:sanctum');

    Route::post('/pay/wallet/{reservationId}', 'pay_private_reservation_Wallet')->middleware('auth:sanctum');
    Route::post('/pay/{reservationId}', 'pay_private_reservation')->middleware('auth:sanctum');

});

Route::prefix('group_reserve')->controller(\App\Http\Controllers\Api\classes\GroupClassController::class)->name('professor_group.')->group(function () {
    Route::get('/', 'index')->middleware('optional.auth')->name('index');
    Route::get('/show/{id}', 'show')->name('show')->middleware('optional.auth');
    Route::get('/get-filter', 'getFilters')->name('get-filter');
    Route::get('/calender/{id}', 'calender');
    Route::post('/comment/{groupClassId}', 'comment')->middleware('auth:sanctum');
    Route::get('/comment/{groupClassId}', 'showComments')->middleware('optional.auth');
    Route::post('/pay/wallet/{reservationId}', 'pay_private_reservation_Wallet')->middleware('optional.auth');
    Route::post('/rate/{groupClassId}', 'rateClassGroup')->middleware('auth:sanctum');
    Route::post('/pay/{reservationId}', 'pay_group_reservation')->middleware('auth:sanctum');
    Route::get('/info/{reservationId}', 'info22');
});

Route::prefix('webinar-reserve')->controller(WebinrController::class)->name('professor_webinar.')->group(function () {
    Route::get('/', 'index')->middleware('optional.auth')->name('index');
    Route::get('/show/{id}', 'show')->name('show')->middleware('optional.auth');
    Route::get('/get-filter', 'getFilters')->name('get-filter');
    Route::get('/calender/{id}', 'calender');
    Route::post('/comment/{groupClassId}', 'comment')->middleware('auth:sanctum');
    Route::get('/comment/{groupClassId}', 'showComments')->middleware('optional.auth');
    Route::post('/rate/{groupClassId}', 'rateClassGroup')->middleware('auth:sanctum');
    Route::post('/pay/{reservationId}', 'pay_group_reservation')->middleware('auth:sanctum');
    Route::get('/info/{reservationId}', 'info22');
    Route::post('/pay/wallet/{reservationId}', 'pay_private_reservation_Wallet')->middleware('auth:sanctum');

    Route::post('/like/{id}', 'toggleLike')->middleware('auth:sanctum');
});


Route::prefix('workshop-reserve')->controller(\App\Http\Controllers\Api\classes\WorkShopController::class)->name('professor_workshop.')->group(function () {
    Route::get('/', 'index')->middleware('optional.auth')->name('index');
    Route::get('/show/{id}', 'show')->name('show')->middleware('optional.auth');
    Route::get('/get-filter', 'getFilters')->name('get-filter');
    Route::get('/calender/{id}', 'calender');
    Route::post('/comment/{groupClassId}', 'comment')->middleware('auth:sanctum');
    Route::get('/comment/{groupClassId}', 'showComments')->middleware('optional.auth');
    Route::post('/rate/{groupClassId}', 'rateClassGroup')->middleware('auth:sanctum');
    Route::post('/pay/{reservationId}', 'pay_group_reservation')->middleware('auth:sanctum');
    Route::get('/info/{reservationId}', 'info22');
    Route::post('/pay/wallet/{reservationId}', 'pay_private_reservation_Wallet')->middleware('auth:sanctum');
    Route::post('/like/{id}', 'toggleLike')->middleware('auth:sanctum');
});


Route::prefix('student-panel')->controller(StudentPanelController::class)->group(function () {
    Route::get('/language', 'showLanguage')->middleware('optional.auth');
    Route::post('/language/store', 'updateLanguage')->middleware('auth:sanctum');
});

Route::prefix('story')->controller(StoryController::class)->name('professor.')->group(function () {
    Route::get('/show/{id}', 'show')->middleware('optional.auth');
    Route::get('/', 'index');
});

Route::get('/teachers/{teacher_id}/time-slots', [ProfessorTimeController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/professor/store/time-slots', [ProfessorTimeController::class, 'store']);
    Route::get('/professor/times', [ProfessorTimeController::class, 'show']);
    Route::put('/time-slots/update/{id}', [ProfessorTimeController::class, 'update']);
    Route::delete('/time-slots/delete/{id}', [ProfessorTimeController::class, 'destroy']);

    Route::prefix('professor-panel')->controller(ProfessorPanelController::class)->name('professor.')->group(function () {
        Route::get('/', 'showProfessor')->name('showProfessors');
        Route::post('/paid', 'paid')->name('paid');
        Route::get('/getProfileImage', 'getProfileImage')->name('getProfileImage');
        Route::post('/uploadProfileImage', 'uploadProfileImage')->name('uploadProfileImage');
        Route::get('/specialties', 'specialties')->name('specialties');
        Route::put('/update', 'updateProfessor')->name('update-professor');
        Route::get('/address', 'showAddress')->name('show-address');
        Route::put('/address/update', 'updateAddress')->name('update-address');
        Route::put('/language/update', 'updateLanguage')->name('update-language');
        Route::get('/video', 'showVideo')->name('video-show');
        Route::post('/video/update', 'updateVideos')->name('update-video');
        Route::post('/storeTranslation', 'storeTranslation')->name('storeTranslation');
        Route::post('/storeAuthor', 'storeAuthor')->name('storeAuthor');
        Route::post('/storeHonor', 'storeHonor')->name('storeHonor');
        Route::post('/storeBlog', 'storeBlog')->name('storeBlog');
        Route::post('/storeCertificate', 'storeCertificate')->name('storeCertificate');
        Route::post('/storeRecord', 'storeRecord')->name('storeRecord');
        Route::post('/storeEducation', 'storeEducation')->name('storeEducation');

    });
    Route::prefix('professor-panel')->controller(ClassPriceController::class)->group(function () {
        Route::get('/price/{classType?}', 'show');
        Route::put('/priceStore', 'store');
    });

    Route::prefix('professor-panel')->controller(ClassTypeController::class)->group(function () {
        Route::get('/type', 'show');
        Route::post('/typeStore', 'store');
    });
    Route::prefix('professor-student')->controller(StudentProfileForProfessorController::class)->group(function () {
        Route::get('/report', 'reportByPeriod');
        Route::get('/show/{id}', 'show');
        Route::get('/private-list/{id}', 'classList');
        Route::get('/show/class/{id}', 'showClass');
        Route::get('/class/{id}', 'class');
        Route::get('/show_d/{id}', 'show_d');
        Route::get('/exam/{id}', 'exam');

    });

    Route::prefix('professor-panel')->controller(ProfessorGoaleController::class)->group(function () {
        Route::get('/goal', 'show');
        Route::get('/goalShow', 'showGroupedGoals');
        Route::post('/goalStore', 'storeProfessorLearningGoals');
    });
    Route::prefix('professor-panel')->controller(ProfessorBook::class)->group(function () {
        Route::get('/book', 'index');
        Route::get('/book/index', 'indexWithoutProduct');
        Route::post('/book/store', 'store');
        Route::post('/book/update/{book}', 'update');
        Route::delete('/book/delete/{book}', 'destroy');
    });


    Route::prefix('professor-panel')->controller(ProfessorTeachingInfoController::class)->group(function () {
        Route::get('/showProfessorDetails', 'showProfessorDetails');
        Route::post('/storeProfessorDetails', 'storeProfessorDetails');
    });
    Route::prefix('story')->controller(StoryController::class)->group(function () {
        Route::post('/store', 'store');
        Route::delete('/delete/{story}', 'destroy');
        Route::post('/like/{id}', 'toggleLike');
        Route::post('/comment/{id}', 'comment');
    });
    Route::prefix('group-class')->controller(GroupClassController::class)->group(function () {
        Route::post('/store', 'store');
        Route::post('/storeSchedule/{groupClass}', 'storeGroupClassSchedule');
        Route::post('/storePrice/{group_classes_id}', 'price');
        Route::post('/storeSuggestions', 'SuggestionsStore');
        Route::get('/getPending', 'getPendingGroupClass');
        Route::get('/info/{id}', 'show_info');
        Route::put('/confirm/{group_class_id}', 'confirmGroupClass');
        Route::delete('/reject/{group_class_id}', 'rejectGroupClass');
        Route::get('/initialData', 'showDetails');
        Route::get('/show/{id}', 'show');
        Route::get('/index', 'index');
        Route::get('/getProfessorBooks', 'getProfessorBooks');
        Route::get('/index2', 'index2');
        Route::get('/professor_classes', 'professor_classes');
        Route::get('/lingo_class', 'lingo_class');
        Route::get('/suggestionClass', 'generateClassSuggestions');
        Route::get('/getStudentsInClass/{id}', 'getStudentsInClass');
        Route::put('/update-class-link/{id}', 'updateClassLink');
        Route::get('/suggestionClass/{id}', 'getClassSuggestionDetails');
    });


    Route::prefix('privet-class')->controller(PrivetClassProfessorController::class)->group(function () {
        Route::get('/', 'indexPrivet');
        Route::get('/trial', 'indexTrial');
        Route::get('/placement', 'indexLevelAssessment');
        Route::post('/placementStore', 'storeLevelAssessment');
        Route::get('/placement/score/{id}', 'showLevelAssessment');
        Route::get('/show/{id}', 'show');
        Route::get('/calender/{id}', 'calender');
        Route::put('/update_link/{id}', 'update_link');
        Route::post('/update_cancellation1/{id}', 'update_cancellation1');
        Route::get('/getStudentsInClass/{id}', 'getStudentsInClass');


    });
    Route::prefix('report')->controller(\App\Http\Controllers\Api\Professors\ReportRegistrationController::class)->group(function () {
        Route::post('/store', 'store');
        Route::get('/show/{id}', 'show');
        Route::get('/info/{id}', 'info');


    });


    Route::prefix('webinar')->controller(WebinarController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/info/{id}', 'show');
        Route::get('/suggestions', 'generateWebinarsSuggestions');
        Route::post('/store', 'store');
        Route::post('/storePrice/{webinar}', 'price');
        Route::delete('/reject/{webinar}', 'rejectWebinar');
        Route::put('/confirm/{webinar}', 'confirm_webinar');
        Route::get('/suggestionClass/{id}', 'getClassSuggestionDetails');
        Route::post('/storeSuggestions', 'SuggestionsStore');
        Route::put('/update-class-link/{id}', 'updateClassLink');
        Route::get('/getStudentsInClass/{id}', 'getStudentsInClass');

    });

    Route::prefix('workshop')->controller(WorkShopsController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/info/{id}', 'show');
        Route::get('/suggestions', 'generateWorkShopsSuggestions');
        Route::post('/store', 'store');
        Route::post('/storePrice/{webinar}', 'price');
        Route::delete('/reject/{webinar}', 'rejectWebinar');
        Route::put('/confirm/{webinar}', 'confirm_webinar');
        Route::get('/suggestionClass/{id}', 'getClassSuggestionDetails');
        Route::post('/storeSuggestions', 'SuggestionsStore');
        Route::get('/getStudentsInClass/{id}', 'getStudentsInClass');
    });

    Route::prefix('comment')->controller(ProfessorsComments::class)->group(function () {
        Route::get('/text', 'TextComments');
        Route::get('/voice', 'VoiceComments');
        Route::get('/video', 'VideoComments');
        Route::get('/show/group/{id}', 'showGroupClassComments');
        Route::get('/show/webinar/{id}', 'showWebinarComments');
        Route::get('/show/workshop/{id}', 'showWorkShopComments');
    });

    Route::prefix('student-panel')->controller(StudentPanelController::class)->middleware('auth:sanctum')->group(function () {
        Route::get('/', 'index');
        Route::get('/show', 'show');
        Route::get('/suggestion', 'suggestion');
        Route::post('/store', 'store');
        Route::get('/getProfileImage', 'getProfileImage');
        Route::post('/uploadProfileImage', 'uploadProfileImage');
        Route::get('/group_classes', 'group_classes');
    });

    Route::prefix('group_reserve')->controller(\App\Http\Controllers\Api\classes\GroupClassController::class)->name('professor_group.')->group(function () {
        Route::post('/like/{id}', 'toggleLike');
    });

    Route::prefix('private-reserve')->controller(\App\Http\Controllers\Api\classes\PrivetClassController::class)->name('professor_group.')->group(function () {
        Route::post('/like/{id}', 'toggleLike');
    });

    Route::prefix('calendar')->controller(Calender::class)->group(function () {
        Route::get('/monthly', 'monthlyCalendar');
        Route::get('/weekly', 'weeklyCalendar');
        Route::get('/daily', 'dailyCalendar');
    });

    Route::prefix('comment')->controller(CommentController::class)->middleware('auth:sanctum')->group(function () {
        Route::post('/like/{id}', 'toggleLike');
        Route::post('/dislike/{id}', 'disLike');
    });

    Route::prefix('dashboard')->controller(ProfessorDashboardController::class)->middleware('auth:sanctum')->name('comment.')->group(function () {
        Route::get('/', 'index');
        Route::get('/incomeChart', 'incomeChart');
    });

    Route::prefix('dashboard')->controller(ProfessorDashboardController::class)->middleware('auth:sanctum')->name('comment.')->group(function () {
        Route::get('/', 'index');
        Route::get('/incomeChart', 'incomeChart');
    });

    Route::prefix('wallet')->controller(WalletController::class)->name('wallet.')->group(function () {
        Route::get('/', 'show');
        Route::get('/transactions', 'ini');
        Route::post('/deposit', 'deposit');
        Route::post('/withdraw', 'withdraw');
    });


//    Route::post('/send-message', function (Request $request) {
//        $message = [
//            'conversation_id' => $request->conversation_id,
//            'sender_id' => $request->sender_id,
//            'text' => $request->text,
//            'sent_at' => now()->toDateTimeString(),
//        ];
//
//        broadcast(new NewMessageSent($message))->toOthers();
//
//        return response()->json(['status' => 'Message sent!']);
//    });
    Route::prefix('blogs')->group(function () {
        Route::post('/like/{id}', [BlogController::class, 'toggleLike']);
        Route::post('/', [BlogController::class, 'store'])->name('blogs.store');
        Route::post('update/{blog}', [BlogController::class, 'update'])->name('blogs.update');
        Route::delete('/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');

    });
    Route::get('/plans', [PlanController::class, 'index']);
    Route::post('/plans/pay', [PlanController::class, 'pay_group_reservation']);
    Route::post('/plans/pay/wallet', [PlanController::class, 'pay_private_reservation_Wallet']);
/////////////////////////// seller ///////////////////////////////////
    Route::get('/seller/dashboard', [SellerDashboardController::class, 'index']);
    Route::prefix('seller/comment')->controller(sellerCommentController::class)->group(function () {
        Route::get('/text', 'TextComments');
        Route::get('/voice', 'VoiceComments');
        Route::get('/video', 'VideoComments');
        Route::get('text/{product}',   'showTextComments');

        Route::get('video/{product}',  'showVideoComments');

        Route::get('voice/{product}',  'showVoiceComments');

    });
    Route::prefix('seller')->controller(SellerController::class)->group(function () {
        Route::prefix('product')->group(function () {
            Route::post('/store', 'store');
            Route::get('/search', 'search');
            Route::get('/index', 'index');
            Route::get('/aryan', 'aryan');
            Route::post('/exist_product', 'exist_product');
            Route::post('/check', 'check');
            Route::get('/initial_data', 'initial_data');
            Route::get('/category', 'category');
            Route::post('/active/{id}', 'active');
            Route::post('/seen_again/{id}', 'seen_again');
            Route::post('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'delete');

        });

        Route::prefix('discount')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [SellerDiscountController::class, 'index'])->name('seller.discounts.index');
            Route::get('/products', [SellerDiscountController::class, 'pro']);
            Route::get('/index', [SellerDiscountController::class, 'discountIndex']);
            Route::delete('/delete/{id}', [SellerDiscountController::class, 'deleteDiscount']);
            Route::post('/product/store', [SellerDiscountController::class, 'storeDiscount'])->name('seller.discounts.store');
            Route::post('/code', [SellerDiscountController::class, 'make_discount']);
            Route::post('/code/update/{id}', [SellerDiscountController::class, 'update_discount']);
            Route::delete('/code/delete/{id}', [SellerDiscountController::class, 'deleteCode']);
        });
    });
    Route::prefix('seller')->middleware('auth:sanctum')->group(function () {
        Route::post('/uploadProfileImage', [SellerProfileController::class, 'uploadProfileImage']);
        Route::get('/info', [SellerProfileController::class, 'info']);
        Route::get('/index', [SellerProfileController::class, 'index']);
        Route::post('/store', [SellerProfileController::class, 'store']);
        Route::post('/media', [SellerProfileController::class, 'mediaStore']);
        Route::get('/media', [SellerProfileController::class, 'media']);
        Route::post('/address', [SellerProfileController::class, 'storeAddress']);
        Route::get('/address', [SellerProfileController::class, 'getAddress']);
        Route::post('/account/store', [SellerProfileController::class, 'storeAccount']);
        Route::post('/is_default/{id}', [SellerProfileController::class, 'is_default']);
        Route::delete('/account/delete/{id}', [SellerProfileController::class, 'deleteAccount']);
        Route::get('/account', [SellerProfileController::class, 'indexAccount']);
        Route::get('/products/score', [SellerProfileController::class, 'products']);
        Route::get('/score', [SellerProfileController::class, 'score']);
        Route::get('/wallet', [SellerWalletController::class, 'show']);
        Route::get('/taraconesh', [SellerWalletController::class, 'taraconesh']);
        Route::get('/darkhast', [SellerWalletController::class, 'darkhast']);
        Route::post('/withdraw', [SellerWalletController::class, 'withdraw']);
    });
    Route::prefix('seller/order')->controller(OrderSellerController::class)->group(function () {
        Route::get('/export', 'exportOrders');
        Route::get('/',  'sellerOrders');
        Route::get('/list',  'transaction');
        Route::post('/rejected/{id}',  'rejected');
        Route::post('/approved/{id}',  'approved');
        Route::post('/send/{id}',  'send');
        Route::get('/show/{id}',  'show');

    });
    ///////////////// student /////////////////

    Route::prefix('student')->middleware('auth:sanctum')->group(function () {

        Route::prefix('book')->group(function () {
            Route::get('/', [StudentBookController::class, 'index']);
            Route::get('index_dow', [StudentBookController::class, 'index_dow']);
            Route::get('suggestedBooks', [StudentBookController::class, 'suggestedBooks']);
            Route::get('show/{id}', [StudentBookController::class, 'showBook']);
            Route::get('download/{id}', [StudentBookController::class, 'show']);
        });

        Route::prefix('calendar')->controller(\App\Http\Controllers\Api\Students\Calender::class)->group(function () {
            Route::get('/monthly', 'monthlyCalendar');
            Route::get('/weekly', 'weeklyCalendar');
            Route::get('/daily', 'dailyCalendar');
        });

        Route::get('favorite', [FavoriteController::class, 'index']);

        Route::prefix('comments')->controller(CommentController::class)->group(function () {
            Route::get('', 'getUserComments');
            Route::delete('/delete/{id}', 'delete');
            Route::post('/update/{id}', 'update');

        });
        Route::prefix('private')->controller(classController::class)->group(function () {
            Route::get('', 'index');
            Route::get('/suggestion', 'suggestion');
            Route::get('/show/{id}', 'show_private');

        });
        Route::prefix('group')->controller(classController::class)->group(function () {
            Route::get('', 'index_group');
            Route::get('/suggestion', 'suggestion_group');
            Route::get('/show/{id}', 'show_group');
        });
        Route::prefix('webinar')->controller(classController::class)->group(function () {
            Route::get('', 'index_webinar');
            Route::get('/suggestion', 'suggestion_webinar');
            Route::get('/show/{id}', 'show_webinar');

        });
        Route::prefix('workshop')->controller(classController::class)->group(function () {
            Route::get('', 'index_workshop');
            Route::get('/suggestion', 'suggestion_workshop');
            Route::get('/show/{id}', 'show_workshop');

        });
        Route::prefix('certificate')->controller(StudentCertificateController::class)->group(function () {
            Route::get('/class', 'class');
            Route::get('/', 'index');
            Route::post('/store', 'store');
            Route::get('/info/{id}', 'info1');
            Route::post('/pay/{reservationId}', 'pay_group_reservation');
            Route::post('/pay/wallet/{reservationId}', 'pay_private_reservation_Wallet');


        });
        Route::prefix('discount')->controller(DiscountForStudentController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/store', 'store');

        });
        Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/store/{id}', 'store');

        });

    });


    ///////// buyer /////////
    Route::prefix('buyer')->middleware('auth:sanctum')->group(function () {

        Route::prefix('profile')->controller(BuyerController::class)->group(function () {
            Route::post('/uploadProfileImage', 'uploadProfileImage');
            Route::get('/', 'show');
            Route::post('/update', 'update');
            Route::get('/addresses', 'listAddresses');
            Route::post('/addresses', 'storeAddress');
            Route::put('/addresses/{id}', 'updateAddress');
            Route::delete('/addresses/{id}', 'deleteAddress');
        });
        Route::get('favorite', [FavoriteController::class, 'bookLikes']);

        Route::prefix('dashboard')->controller(DashboardBuyerController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/discount', 'discount');


        });

        Route::prefix('order')->controller(MyOrderController::class)->group(function () {
            Route::get('/', 'index');
            Route::get('/show/{id}', 'show');
            Route::post('/tracking', 'tracking');
            Route::post('/return_order/{id}', 'returnOrder');
            Route::post('/cancel/{id}', 'cancel');
            Route::post('/rate', 'rateDeliveredOrder');

        });

    });


});

