@include('admin.upad')

<div class="container mx-auto p-6 max-w-lg">
    <h1 class="text-2xl mb-6">ویرایش تنظیمات</h1>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-200 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4">
        @csrf

        <!-- فیلدهای قبلی -->
        <div>
            <label class="block mb-1 font-semibold">قیمت دلار (dolar_price)</label>
            <input type="number" step="any" name="dolar_price" value="{{ old('dolar_price', $settings['dolar_price']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('dolar_price')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">تیپاکس (tipax)</label>
            <input type="number" step="any" name="tipax" value="{{ old('tipax', $settings['tipax']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('tipax')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">پست (post)</label>
            <input type="number" step="any" name="post" value="{{ old('post', $settings['post']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('post')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">مدرک آنلاین (online_certificate)</label>
            <input type="number" step="any" name="online_certificate" value="{{ old('online_certificate', $settings['online_certificate']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('online_certificate')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">مدرک فیزیکی (physical_certificate)</label>
            <input type="number" step="any" name="physical_certificate" value="{{ old('physical_certificate', $settings['physical_certificate']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('physical_certificate')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- فیلدهای درصدی اضافه شده -->

        <div>
            <label class="block mb-1 font-semibold">درصد کلاس گروهی</label>
            <input type="number" step="any" min="0" max="100" name="group_class_percentage" value="{{ old('group_class_percentage', $settings['group_class_percentage']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('group_class_percentage')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">درصد ورکشاپ</label>
            <input type="number" step="any" min="0" max="100" name="workshop_percentage" value="{{ old('workshop_percentage', $settings['workshop_percentage']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('workshop_percentage')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">درصد کلاس خصوصی</label>
            <input type="number" step="any" min="0" max="100" name="private_class_percentage" value="{{ old('private_class_percentage', $settings['private_class_percentage']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('private_class_percentage')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-1 font-semibold">درصد وبینار</label>
            <input type="number" step="any" min="0" max="100" name="webinar_percentage" value="{{ old('webinar_percentage', $settings['webinar_percentage']->value ?? '') }}"
                   class="w-full border p-2 rounded" required>
            @error('webinar_percentage')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">ذخیره تنظیمات</button>
    </form>
</div>
