<?php

use App\Http\Controllers\AdminExam\ExamController;
use App\Http\Controllers\AdminExam\ExamQuestionController;
use App\Http\Controllers\Api\classes\GroupClassController;
use App\Http\Controllers\Api\classes\PrivetClassController;
use App\Http\Controllers\Api\classes\WebinrController;
use App\Http\Controllers\Api\classes\WorkShopController;
use App\Http\Controllers\Api\Professors\PlanController;
use App\Http\Controllers\Api\Professors\ProfessorPanelController;
use App\Http\Controllers\Api\shop\OrderController;
use App\Http\Controllers\Api\Students\StudentCertificateController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/mt', function () {
    Artisan::Call('migrate', ['--force' => true]);

    dd(Artisan::output());
});


Route::get('/optimize', function () {
    Artisan::call('optimize');
    dd(Artisan::output());
});

Route::get('/seed', function () {
    Artisan::call('db:seed');
    dd(Artisan::output());
});

Route::get('/ops', function () {
    Artisan::call('migrate:fresh');

    return response()->json(['message' => 'Database migrated fresh successfully']);
});
Route::get('/optimize', function () {
    Artisan::call('optimize');

    dd(Artisan::output());
});
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');

    Route::get('/exams/{id}', [ExamController::class, 'show'])->name('exams.show');
    Route::post('/exams/{id}/parts', [ExamController::class, 'storePart'])->name('exams.parts.store');
    Route::delete('/exams/{exam}/parts/{part}', [ExamController::class, 'destroy_part'])->name('exams.parts.destroy');
    Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
    Route::put('/exams/{exam}/parts/{part}', [ExamController::class, 'update_part'])->name('exams.parts.update');

    Route::get('/exams/{id}/edit', [ExamController::class, 'edit'])->name('exams.edit');

// بروزرسانی آزمون
    Route::put('/exams/{id}', [ExamController::class, 'update'])->name('exams.update');

// حذف آزمون
    Route::delete('/exams/{id}', [ExamController::class, 'destroy'])->name('exams.destroy');

// web.php
    Route::get('/exams/{exam}/students', [ExamController::class, 'showStudents'])->name('exams.students');

    Route::put('questions/update/{id}', [ExamQuestionController::class, 'q_update'])->name('q_update');
    Route::get('questions/edit/{id}', [ExamQuestionController::class, 'q_edit'])->name('q_edit');
    Route::post('exam_questions/clone/{part}/{question}', [ExamQuestionController::class, 'clone'])
        ->name('exam_questions.clone');


    Route::prefix('exam-parts/{examPartId}/questions')->name('exam_questions.')->group(function () {
        Route::get('/', [ExamQuestionController::class, 'index'])->name('index');
        Route::post('/', [ExamQuestionController::class, 'store'])->name('store');

        Route::delete('/{questionId}', [ExamQuestionController::class, 'destroy'])->name('destroy');
    });
});

///////////////////


Route::get('/zarin_callback', [GroupClassController::class, 'zarin_callback'])->name('zarin_callback');
Route::get('/zarin_callback_webinar', [WebinrController::class, 'zarin_callback_webinar'])->name('zarin_callback_webinar');
Route::get('/zarin_callback_work', [WorkShopController::class, 'zarin_callback_work'])->name('zarin_callback_work');
Route::get('/zarin_callback/private', [PrivetClassController::class, 'zarin_callback'])->name('zarin_callback_private');
Route::get('/zarin_callback_wallet', [WalletController::class, 'zarin_callback_wallet'])->name('zarin_callback_wallet');
Route::get('/zarin_callback_plan', [PlanController::class, 'zarin_callback_plan'])->name('zarin_callback_plan');
Route::get('/zarin_callback_certificate', [StudentCertificateController::class, 'zarin_callback']);
Route::get('/zarin_callback_product', [OrderController::class, 'zarin_callback_product']);
Route::get('/zarin_callback_paid', [ProfessorPanelController::class, 'zarin_callback_paid']);
Route::get('/zarin_callback_exam', [\App\Http\Controllers\Api\Students\ExamPayments\ExamPaymentController::class, 'zarin_callback_exam']);
Route::get('zarin_callback_wallet', [WalletController::class, 'zarin_callback_wallet']);
Route::get('zarin_callback_exam', [\App\Http\Controllers\Api\Students\exam\Exam1Controller::class, 'zarin_callback_exam']);
/////////////////////////////////////
////////////////////////////////////admin
/////////////////////////////////////
///

Route::get('/admindashboard/tayid_asatid', [AdminDashboardController::class, 'tayid_asatid'])->name('tayid_asatid');
Route::get('/admindashboard/professorss', [AdminDashboardController::class, 'tayid_asatid'])->name('professors.index');
Route::put('/professors/{id}/priority', [AdminDashboardController::class, 'updatePriority'])->name('professors.updatePriority');
Route::put('/professors/{id}/toggle-visibility', [AdminDashboardController::class, 'toggleVisibility'])->name('professors.toggleVisibility');
Route::post('/admindashboard/tayid_asatid/{id}/approve', [AdminDashboardController::class, 'approveProfessor'])->name('approve.professor');
Route::post('/admindashboard/tayid_asatid/{id}/reject', [AdminDashboardController::class, 'rejectProfessor'])->name('reject.professor');
Route::put('/admindashboard/tayid_asatid/{professor}', [AdminDashboardController::class, 'updateProfessor'])->name('update.professor');
Route::delete('/admindashboard/tayid_asatid/{id}', [AdminDashboardController::class, 'delete_professor'])->name('delete.professor');
//////////
Route::get('/admindashboard/vared_kardan_zabanha', [AdminDashboardController::class, 'vared_kardan_zabanha'])->name('vared_kardan_zabanha');
Route::post('/admindashboard/vared_kardan_zabanha', [AdminDashboardController::class, 'vared_kardan_zabanha_store'])->name('languages.store');
Route::get('/admindashboard/languages/{id}/edit', [AdminDashboardController::class, 'vared_kardan_zabanha_edit'])->name('languages.edit');
Route::put('/admindashboard/languages/{id}', [AdminDashboardController::class, 'vared_kardan_zabanha_update'])->name('languages.update');
Route::delete('/admindashboard/languages/{id}', [AdminDashboardController::class, 'vared_kardan_zabanha_destroy'])->name('languages.destroy');
Route::get('/admindashboard/languages', [AdminDashboardController::class, 'index'])->name('languages.index');
/////
Route::get('/admindashboard/vared_kardan_lahje', [AdminDashboardController::class, 'vared_kardan_lahje'])->name('vared_kardan_lahje');
Route::post('/admindashboard/vared_kardan_lahje', [AdminDashboardController::class, 'vared_kardan_lahje_store'])->name('lahje.store');
Route::get('/admindashboard/vared_kardan_lahje/{id}/edit', [AdminDashboardController::class, 'vared_kardan_lahje_edit'])->name('lahje.edit');
Route::put('/admindashboard/vared_kardan_lahje/{id}', [AdminDashboardController::class, 'vared_kardan_lahje_update'])->name('lahje.update');
Route::delete('/admindashboard/vared_kardan_lahje/{id}', [AdminDashboardController::class, 'vared_kardan_lahje_destroy'])->name('lahje.destroy');
///
///
Route::get('/admindashboard/tayid_klas_goroohi', [AdminDashboardController::class, 'tayid_klas_goroohi'])->name('tayid_klas_goroohi');
Route::post('/admin/tayid_klas_goroohi/approve/{id}', [AdminDashboardController::class, 'approve_tayid_klas_goroohi'])->name('tayid_klas_goroohi.approve');
Route::post('/admin/tayid_klas_goroohi/reject/{id}', [AdminDashboardController::class, 'reject_tayid_klas_goroohi'])->name('tayid_klas_goroohi.reject');
/////

Route::get('/admindashboard/workshops', [AdminDashboardController::class, 'workshops'])->name('workshops');
Route::post('/admin/workshops/approve/{id}', [AdminDashboardController::class, 'approve_workshops'])->name('workshops.approve');
Route::post('/admin/workshops/reject/{id}', [AdminDashboardController::class, 'reject_workshops'])->name('workshops.reject');
//
//
Route::get('/admindashboard/tayid_webinar', [AdminDashboardController::class, 'tayid_webinar'])->name('tayid_webinar');
Route::post('/admin/tayid_webinar/approve/{id}', [AdminDashboardController::class, 'approve_tayid_webinar'])->name('tayid_webinar.approve');
Route::post('/admin/tayid_webinar/reject/{id}', [AdminDashboardController::class, 'reject_tayid_webinar'])->name('tayid_webinar.reject');
//
Route::get('/admindashboard/canceled_classes', [AdminDashboardController::class, 'canceled_classes'])->name('canceled_classes');


Route::get('/admindashboard/tayid_kament', [AdminDashboardController::class, 'comments'])->name('comments');
Route::post('/admin/comments/approve/{id}', [AdminDashboardController::class, 'approve'])->name('admin.comments.approve');
Route::post('/admin/comments/reject/{id}', [AdminDashboardController::class, 'reject'])->name('admin.comments.reject');


//////////////////
////////////////////
/////////////////////shop

Route::get('/admindashboard/add_book', [AdminDashboardController::class, 'add_book'])->name('add_book');
Route::post('/admindashboard/add_book/store', [AdminDashboardController::class, 'store_book'])->name('books.store');
Route::put('/admindashboard/books/{id}', [AdminDashboardController::class, 'update'])->name('books.update');
Route::delete('/admindashboard/books/{id}', [AdminDashboardController::class, 'destroy'])->name('books.destroy');


Route::get('/admindashboard/discount', [AdminDashboardController::class, 'discount'])->name('discount');
Route::post('/admindashboard/storeDiscount/store', [AdminDashboardController::class, 'storeDiscount'])->name('discount.store');
Route::put('/admindashboard/discount_update/{id}', [AdminDashboardController::class, 'discount_update'])->name('discount.update');
Route::delete('/admindashboard/discount_destroy/{id}', [AdminDashboardController::class, 'discount_destroy'])->name('discount.destroy');


Route::get('admindashboard/educational-products', [AdminDashboardController::class, 'indexEducationalProduct'])->name('educational-products.index');
Route::get('admindashboard/educational-products/create', [AdminDashboardController::class, 'createEducationalProduct'])->name('educational-products.create');
Route::post('admindashboard/educational-products', [AdminDashboardController::class, 'storeEducationalProduct'])->name('educational-products.store');
Route::get('admindashboard/educational-products/{educationalProduct}/edit', [AdminDashboardController::class, 'editEducationalProduct'])->name('educational-products.edit');
Route::put('admindashboard/educational-products/{educationalProduct}', [AdminDashboardController::class, 'updateEducationalProduct'])->name('educational-products.update');
Route::delete('admindashboard/educational-products/{educationalProduct}', [AdminDashboardController::class, 'destroyEducationalProduct'])->name('educational-products.destroy');


//////
///
Route::get('/admindashboard/book_confirmation', [AdminDashboardController::class, 'book_confirmation'])->name('book_confirmation');
Route::post('/admindashboard/book/confirm/{id}', [AdminDashboardController::class, 'confirm_book'])->name('book.confirm');
Route::post('/admindashboard/book/reject/{id}', [AdminDashboardController::class, 'reject_book'])->name('book.reject');

///////////
///chat
Route::get('/admindashboard/conversations', [AdminDashboardController::class, 'indexChat'])->name('admin.conversations.index');
Route::get('/admindashboard/conversations/{id}/messages', [AdminDashboardController::class, 'ajaxChatMessages'])->name('admin.conversations.messages');

///
///
/// blog
Route::get('/admindashboard/blog', [AdminDashboardController::class, 'blog'])->name('blog');
Route::post('/admindashboard/add_blog/', [AdminDashboardController::class, 'add_blog'])->name('add_blog.add');
Route::put('/admindashboard/blog/{id}', [AdminDashboardController::class, 'blog_update'])->name('blog.update');
Route::delete('/admindashboard/blog/{id}', [AdminDashboardController::class, 'blog_destroy'])->name('blog.destroy');
Route::post('/admin/blog/approve/{id}', [AdminDashboardController::class, 'blog_approve'])->name('blog.approve');
Route::post('/admin/blog/reject/{id}', [AdminDashboardController::class, 'blog_reject'])->name('blog.reject');
Route::post('/upload-image', [AdminDashboardController::class, 'uploadImage'])->name('upload.image');

///
Route::get('/admindashboard/consultation_form', [AdminDashboardController::class, 'consultation_form'])->name('consultation_form');
Route::delete('/admindashboard/consultation/{id}', [AdminDashboardController::class, 'consultation_destroy'])->name('consultation.destroy');

///
/// /
///
Route::get('/admin/tickets', [AdminDashboardController::class, 'tickets'])->name('admin.tickets');

Route::get('/admin/tickets/{id}', [AdminDashboardController::class, 'getTicketMessages'])->name('admin.ticket.messages');

Route::post('/admin/tickets/{id}/close', [AdminDashboardController::class, 'ticketClose'])->name('admin.ticket.close');

Route::post('/admin/tickets/{id}/reopen', [AdminDashboardController::class, 'ticketReopen'])->name('admin.ticket.reopen');

Route::post('/admin/tickets/{id}/complete', [AdminDashboardController::class, 'ticketComplete'])->name('admin.ticket.complete');
Route::post('/admin/tickets/{id}/reply', [AdminDashboardController::class, 'ticketReply'])->name('admin.ticket.reply');
//


Route::get('/admin/contacts', [AdminDashboardController::class, 'contacts'])->name('admin.contacts');
Route::post('/admin/contacts/{id}/mark-seen', [AdminDashboardController::class, 'contacts_markSeen'])->name('admin.contacts.markSeen');

//

Route::get('admindashboard/slider', [AdminDashboardController::class, 'slider'])->name('admin.slider');
Route::put('/admindashboard/slider_update/{id}', [AdminDashboardController::class, 'slider_update'])->name('slider.update');

Route::post('admindashboard/slider', [AdminDashboardController::class, 'slider_store_update'])->name('admin.slider.store_update');

Route::delete('admindashboard/slider/{id}', [AdminDashboardController::class, 'slider_destroy'])->name('admin.slider.destroy');

////
///
///
///
///
///
Route::get('/admindashboard/bankexans', [AdminDashboardController::class, 'bankexans'])->name('admin.bankexans');
Route::get('/admindashboard/bankexans/create', [AdminDashboardController::class, 'createBankExan'])->name('admin.bankexans.create');
Route::post('/admindashboard/bankexans/store', [AdminDashboardController::class, 'storeBankExan'])->name('admin.bankexans.store');
Route::get('/admindashboard/bankexans/create-step2', [AdminDashboardController::class, 'createExamPart'])->name('admin.bankexans.create_step2');
Route::post('/admindashboard/bankexans/parts/store', [AdminDashboardController::class, 'storeExamPart'])->name('admin.bankexans.parts.store');
Route::get('/admindashboard/bankexans/questions/create', [AdminDashboardController::class, 'createQuestion'])->name('admin.questions.create');
Route::post('/admindashboard/bankexans/questions/store', [AdminDashboardController::class, 'storeQuestion'])->name('admin.bankexans.questions.store');


Route::get('/admindashboard/news/{id?}', [AdminDashboardController::class, 'news'])->name('news');
Route::post('/admindashboard/news/store_update', [AdminDashboardController::class, 'news_store_update'])->name('news.store_update');
Route::delete('/admindashboard/news/destroy/{id}', [AdminDashboardController::class, 'news_destroy'])->name('news.destroy');
///
///
Route::get('admindashboard/Baner', [AdminDashboardController::class, 'Baner'])->name('admin.Baner');
Route::put('admindashboard/Baner_update/{id}', [AdminDashboardController::class, 'Baner_update'])->name('Baner.update');


Route::get('admindashboard/VideoBaner', [AdminDashboardController::class, 'VideoBaner'])->name('VideoBaner.Baner');
Route::put('admindashboard/videoBanerUpdate/{id}', [AdminDashboardController::class, 'videoBanerUpdate'])->name('VideoBaner.update');

///
Route::get('admindashboard/story', [AdminDashboardController::class, 'story'])->name('admin.story');
Route::post('admindashboard/story/approve/{id}', [AdminDashboardController::class, 'story_approve'])->name('story.approve');
Route::post('admindashboard/story/reject/{id}', [AdminDashboardController::class, 'story_reject'])->name('story.reject');
Route::delete('admindashboard/story/delete/{id}', [AdminDashboardController::class, 'story_delete'])->name('story.delete');

//
//
//
Route::get('admindashboard/publications', [AdminDashboardController::class, 'publications'])->name('admin.publications');
Route::post('admindashboard/publications/store', [AdminDashboardController::class, 'publications_store'])->name('admin.publications.store');
Route::post('admindashboard/publications/update/{id}', [AdminDashboardController::class, 'publications_update'])->name('admin.publications.update');
Route::post('admindashboard/publications/delete/{id}', [AdminDashboardController::class, 'publications_delete'])->name('admin.publications.delete');
Route::delete('admindashboard/story/bulk-delete', [AdminDashboardController::class, 'story_bulk_delete'])->name('story.bulk_delete');


//////

Route::get('/admindashboard/wallets', [AdminDashboardController::class, 'wallets'])->name('admin.wallets');
///
///
Route::get('/admindashboard/settings', [AdminDashboardController::class, 'settings'])->name('admin.settings');
Route::post('/admindashboard/settings/update', [AdminDashboardController::class, 'settings_update'])->name('admin.settings.update');

//
//
Route::get('/admindashboard/exam_payments', [AdminDashboardController::class, 'examPayments'])->name('admin.examPayments');
//

Route::get('/admindashboard/categoryicon', [AdminDashboardController::class, 'categoryicon'])->name('admin.categoryicon');
Route::post('/admindashboard/categoryicon/update', [AdminDashboardController::class, 'categoryicon_update'])->name('admin.categoryicon.update');



////////

Route::get('/admindashboard/orders', [AdminDashboardController::class, 'orders'])->name('admin.orders');
Route::post('/admindashboard/orders/update', [AdminDashboardController::class, 'updateOrder'])->name('admin.orders.update');

///
///
Route::get('/admindashboard/transactions', [AdminDashboardController::class, 'transactions']);
Route::post('/admindashboard/transactions/update', [AdminDashboardController::class, 'transactions_update'])->name('transactions.update');

//

//
Route::get('admindashboard/Pay_the_teacher', [AdminDashboardController::class, 'Pay_the_teacher'])->name('Pay_the_teacher');
Route::put('admindashboard/Pay_the_teacher_update/{id}', [AdminDashboardController::class, 'Pay_the_teacher_update'])->name('Pay_the_teacher.update');

//
Route::get('admindashboard/teacher_video', [AdminDashboardController::class, 'teacher_video'])->name('teacher_video');
Route::get('admindashboard/about_video', [AdminDashboardController::class, 'about_video'])->name('about_video');
Route::get('admindashboard/teaching_example_video', [AdminDashboardController::class, 'teaching_example_video'])->name('teaching_example_video');

//

Route::get('admindashboard/Seller_confirmation', [AdminDashboardController::class, 'sellerConfirmation'])->name('Seller_confirmation');
Route::post('admindashboard/seller/{id}/status', [AdminDashboardController::class, 'updateSellerStatus']);


Route::delete('/admindashboard/seller/{id}', [AdminDashboardController::class, 'destroySeller'])->name('admin.seller.destroy');

/////////
///
///
Route::get('/admindashboard/questionnaires', [AdminDashboardController::class, 'questionnaires'])->name('questionnaires.index');
Route::put('/admindashboard/questionnaires/{id}', [AdminDashboardController::class, 'questionnaires_update'])->name('questionnaires.update');
Route::delete('/admindashboard/questionnaires/{id}', [AdminDashboardController::class, 'questionnaires_destroy'])->name('questionnaires.destroy');
