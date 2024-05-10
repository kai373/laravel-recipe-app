{{--
<!-- ヘッダーを作成してください -->
<!-- すべてのページで読み込むものです -->
<!-- ヘッダーには、以下のリンクを作成してください -->
<!-- ロゴ -->
<!-- ホーム -->
<!-- マイページ -->
<!-- ログイン　-->
<!-- ログアウト -->
<!-- ユーザー登録 -->
<!-- ログインしているときとそうでないときで出し分けたいです -->
<!-- ログインしているときは、マイページとログアウトを表示 -->
<!-- ログインしていないときは、ログインとユーザー登録を表示 -->
<!-- ログインしているときは、ログアウトを押すとログアウト処理を行い、ログインしていないときは、ログインを押すとログインページに遷移するようにしてください -->
<!-- ログインしているときは、マイページを押すとマイページに遷移するようにしてください -->
<!-- ログインしていないときは、ユーザー登録を押すとユーザー登録ページに遷移するようにしてください -->
<!-- ロゴを押すと、ホームに遷移するようにしてください -->
<!-- ログインしているときは、ユーザー名を表示してください -->
<!-- ログインしていないときは、ゲストと表示してください -->
<!-- ログインしているときは、ユーザー名を押すとマイページに遷移するようにしてください -->
<!-- TailwindCSSで適当にスタイリングしてください -->
--}}
<section class="flex bg-white shadow h-10 py-2 border-b-2">
  <div class="container mx-auto flex justify-between">
    <p class="">今日も料理が楽しみだ！</p>
    <div class="flex">
  @auth
      <a href="{{route('profile.edit')}}" class="ml-auto">マイページ</a>
  @endauth
  @guest
      <a href="{{route('register')}}" class="mr-2">ユーザー登録（無料）</a>
      <a href="{{route('login')}}" class="">ログイン</a>
  @endauth
    </div>
  </div>
</section>

{{-- <header class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <a href="{{ route('home') }}" class="text-lg font-semibold">
            <img src="/path/to/logo.png" alt="Logo" class="h-8">
        </a>
        <nav>
            <ul class="flex space-x-4">
                @auth
                    <li><a href="{{ route('home') }}" class="text-gray-700 hover:text-gray-900">ホーム</a></li>
                    <li><a href="{{ route('mypage') }}" class="text-gray-700 hover:text-gray-900">マイページ</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-700 hover:text-gray-900">ログアウト</button>
                        </form>
                    </li>
                    <li><a href="{{ route('mypage') }}" class="text-gray-700 hover:text-gray-900">{{ Auth::user()->name }}</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900">ログイン</a></li>
                    <li><a href="{{ route('register') }}" class="text-gray-700 hover:text-gray-900">ユーザー登録</a></li>
                    <li class="text-gray-700">ゲスト</li>
                @endauth
            </ul>
        </nav>
    </div>
</header> --}}
