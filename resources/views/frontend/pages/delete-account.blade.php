@extends('frontend.layouts.master')

@section('title', 'Delete Account')

@section('content')
<style>
    .delete-account-hero {
        padding: 130px 0 70px;
        background: linear-gradient(135deg, #f5f3ff, #ffffff);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .delete-account-hero h1 {
        font-size: 68px;
        font-weight: 700;
        color: #333;
        line-height: 1.2;
        margin-bottom: 25px;
    }

    .delete-account-hero h1 span {
        color: #7c3aed;
    }

    .delete-account-hero .subtitle {
        font-size: 20px;
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
    }

    .delete-account-hero .description {
        font-size: 16px;
        color: #777;
        max-width: 600px;
        margin: 0 auto 30px;
        line-height: 1.6;
    }

    .delete-account-hero .divider {
        width: 80px;
        height: 4px;
        background-color: #7c3aed;
        margin: 0 auto;
        border-radius: 2px;
    }

    .delete-account-info {
        padding: 60px 0 80px;
        background-color: #f9f9f9;
        text-align: center;
    }

    .delete-account-info h2 {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 30px;
    }

    .delete-account-info p {
        font-size: 16px;
        color: #666;
        line-height: 1.8;
        max-width: 700px;
        margin: 0 auto 15px;
    }

    .delete-account-info p strong {
        color: #333;
    }

    /* FORM SECTION */
    .deletion-request-section {
        padding: 80px 0;
        background: #ffffff;
    }

    .deletion-form-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        max-width: 1100px;
        margin: 0 auto;
    }

    .deletion-row {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
    }

    /* IMAGE SIDE */
    .deletion-img-col {
        flex: 1 1 45%;
        box-sizing: border-box;
        background: linear-gradient(135deg, #f5f3ff, #ffffff);
        display: flex;
        align-items: stretch;
        justify-content: center;
        align-self: stretch;
        padding: 0;
        min-height: 100%;
    }

    .deletion-img-col img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        border-radius: 0;
    }

    /* FORM SIDE */
    .deletion-form-col {
        flex: 1 1 55%;
        box-sizing: border-box;
        padding: 50px 60px;
        align-self: center;
    }

    .deletion-form-col h3 {
        font-size: 30px;
        font-weight: 700;
        color: #333;
        margin-bottom: 30px;
    }

    .border-input {
        width: 100%;
        border: none;
        border-bottom: 1px solid #ddd;
        padding: 12px 0;
        margin-bottom: 25px;
        font-size: 15px;
        background: transparent;
        outline: none;
        transition: 0.3s;
    }

    .border-input::placeholder {
        color: #888;
    }

    .border-input:focus {
        border-bottom: 2px solid #7c3aed;
    }

    .submit-btn-red {
        background-color: #e63946;
        color: white;
        border: none;
        padding: 12px 35px;
        border-radius: 30px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        width: 100%;
    }

    .submit-btn-red:hover {
        background-color: #d62828;
        transform: translateY(-2px);
    }

    /* RESPONSIVE */
    @media (max-width: 991px) {
        .delete-account-hero h1 {
            font-size: 44px;
        }
    }

    @media (max-width: 768px) {
        .deletion-row {
            flex-direction: column;
        }

        .deletion-img-col {
            display: none;
        }

        .deletion-form-col {
            padding: 40px 25px;
        }
    }
</style>
    
 <section class="delete-account-hero">
        <div class="container">
            <h1 class="wow fadeInUp" data-wow-delay="0.1s">Its Sad<br><span>To See You</span></h1>
            <p class="subtitle wow fadeInUp" data-wow-delay="0.2s">We're Sorry To See You Go!</p>
            <p class="description wow fadeInUp" data-wow-delay="0.3s">If You're Certain About Deleting Your Account, Please Read The Following Important Information</p>
            <div class="divider wow fadeInUp" data-wow-delay="0.4s"></div>
        </div>
    </section>

    <section class="delete-account-info">
        <div class="container">
            <h2 class="wow fadeInUp" data-wow-delay="0.5s">What Will Happen Next?</h2>
            
            <p class="wow fadeInUp" data-wow-delay="0.6s">
                <strong>Permanent Deletion:</strong> All your personal data will be permanently deleted.
            </p>
            <p class="wow fadeInUp" data-wow-delay="0.7s">
                <strong>Irreversible Action:</strong> This action cannot be undone.
            </p>
            <p class="wow fadeInUp" data-wow-delay="0.8s">
                <strong>Subscription Cancellation:</strong> Any outstanding subscriptions will be canceled.
            </p>
            
            <p class="wow fadeInUp" data-wow-delay="0.9s" style="margin-top: 25px;">
                If you still wish to proceed, please fill the form below:
            </p>
        </div>
    </section>

   <section id="deletionForm" class="deletion-request-section">
    <div class="container">

        <div class="deletion-form-card wow fadeInUp">

            <div class="deletion-row">

                <!-- IMAGE SIDE -->
                <div class="deletion-img-col">
                    <img src="{{ asset('landing/img/delete.png') }}" alt="Delete">
                </div>

                <!-- FORM SIDE -->
                <div class="deletion-form-col">
                    @if(session('success'))
                            <div class="alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif
                    <h3>Request Account Deletion</h3>
                    <form action="{{ route('web.delete.account') }}" method="POST">
                         @csrf
                        <input type="text" name="name" class="border-input" placeholder="Full Name *" value="{{ old('name') }}" required>
                        <input type="phone" name="phone" class="border-input" placeholder="Your phone *" value="{{ old('phone') }}" required>
                        <!-- <input type="email" class="border-input" placeholder="Your Email *" required> -->

                        <textarea class="border-input" rows="3" name="reason"
                            placeholder="Reason for deleting account (Optional)"></textarea>

                        <div class="g-recaptcha mb-3"
                            data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>

                        <button type="submit" class="submit-btn-red">
                            Submit Request
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>
</section>
@endsection
