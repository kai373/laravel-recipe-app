<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Step;
use App\Http\Requests\RecipeCreateRequest;
use App\Http\Requests\RecipeUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    public function home()
    {
        // get all recipes

        $recipes = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.created_at', 'desc')
            ->limit(3)
            ->get();

        // ↓↓↓ 同じ SQL を実行するが、Eloquent のメソッドを使って書き換える ↓↓↓
        // $recipes = Recipe::with('user:id,name')
        //     // selectは省略
        //     ->select('id', 'title', 'description', 'created_at', 'image')
        //     ->orderBy('created_at', 'desc')
        //     ->limit(3)
        //     ->get();

        // Recipe::with('user:id,name') というコードは、Eloquent ORMのイーガーローディングを使用しています。これは、Recipe モデルに関連付けられている User モデルのデータを事前にロードするために使われます。ここでの user:id,name は、関連する User モデルから id と name のみを選択して取得することを指示しています。
        // この方法により、各レシピに対してユーザー情報を取得する際に、全てのユーザー属性を取得する代わりに、必要な id と name のみを取得します。これにより、データの取得効率が向上し、アプリケーションのパフォーマンスが改善されます。

        // dd($recipes);

        $popular = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'recipes.views', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.views', 'desc')
            ->limit(2)
            ->get();
        // dd($popular);

        return view('home', compact('recipes', 'popular'));
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->all();
        // dd($filters);

        // get all recipes
        $query = Recipe::query()->select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name', DB::raw('AVG(reviews.rating) as rating'))
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->leftjoin('reviews', 'reviews.recipe_id', '=', 'recipes.id')
            ->groupBy('recipes.id')
            ->orderBy('recipes.created_at', 'desc');

        if (!empty($filters)) {
            // もしカテゴリーが選択されていたら
            if (!empty($filters['categories'])) {
                // カテゴリーで絞り込み選択したカテゴリーIDが含まれているレシピを取得
                $query->whereIn('recipes.category_id', $filters['categories']);
            }
            if (!empty($filters['rating'])) {
                // レビューの平均値が選択した評価以上のレシピを取得
                $query->havingRaw('AVG(reviews.rating) >= ?', [$filters['rating']])
                    ->orderBy('rating', 'desc');
            }
            // もしキーワードが入力されていたら
            if (!empty($filters['title'])) {
                // キーワードで絞り込み
                $query->where('recipes.title', 'like', '%' . $filters['title'] . '%');
            }
        }
        $recipes = $query->paginate(5);
        // dd($recipes);

        $categories = Category::all();

        return view('recipes.index', compact('recipes', 'categories', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();

        return view('recipes.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RecipeCreateRequest $request)
    {
        $posts = $request->all();
        $ulid = Str::ulid();
        // dd($posts);

        $image = $request->file('image');

        $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
        // dd($path);

        $url = Storage::url($path);

        // Storage::disk('s3')->url($path) がエラーを引き起こす場合、Storage::url($path) を使用してみてください。これは、デフォルトのディスク設定を使用してファイルのURLを生成します。もし s3 ディスクがデフォルトで設定されている場合、この方法で問題が解決するはずです。もしデフォルトが s3 でない場合は、config/filesystems.php で s3 をデフォルトディスクとして設定する必要があります。

        // dd($url);

        try {
            DB::beginTransaction();
            Recipe::insert([
                'id' => $ulid,
                'title' => $posts['title'],
                'description' => $posts['description'],
                'category_id' => $posts['category'],
                'image' => $url,
                'user_id' => Auth::id(),
            ]);
            // $posts['ingredients'] =$posts['ingredients'][0]['name']
            // $posts['ingredients'] =$posts['ingredients'][0]['quantity']
            $ingredients = [];
            foreach ($posts['ingredients'] as $key => $ingredient) {
                $ingredients[$key] = [
                    'recipe_id' => $ulid,
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity']
                ];
            }
            Ingredient::insert($ingredients);
            $steps = [];
            foreach ($posts['steps'] as $key => $step) {
                $steps[$key] = [
                    'recipe_id' => $ulid,
                    'step_number' => $key + 1,
                    'description' => $step
                ];
            }
            Step::insert($steps);
            // dd($steps);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::debug(print_r($th->getMessage(), true));
            throw $th;
        }
        return redirect()
            ->route('recipe.show', ['id' => $ulid])
            ->with('feedback.success', 'レシピを投稿しました!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // $recipe = Recipe::find($id);
        $recipe = Recipe::with('ingredients', 'steps', 'reviews.user', 'user')
            ->where('recipes.id', $id)
            ->first();
        // dd($recipe);
        $recipe_recode = Recipe::find($id);
        // dd($recipe_recode);
        $recipe_recode->increment('views');

        // レシピの投稿者とログインユーザーが一致しているか確認
        $is_my_recipe = false;
        if (Auth::check() && Auth::id() === $recipe->user_id) {
            $is_my_recipe = true;
        }

        return view('recipes.show', compact('recipe', 'is_my_recipe'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $recipe = Recipe::with('ingredients', 'steps', 'reviews.user', 'user')
            ->where('recipes.id', $id)
            ->first()->toArray();

        if (!Auth::check() || Auth::id() !== $recipe['user_id']) {
            abort(403);
            // return redirect()
            //     ->route('recipe.show', ['id' => $id])
            //     ->with('feedback.error', '他のユーザーのレシピは編集できません');
        }

        $categories = Category::all();

        return view('recipes.edit', compact('recipe', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RecipeUpdateRequest $request, string $id)
    {
        $posts = $request->all();
        // dd($posts);

        $update_array = [
            'title' => $posts['title'],
            'description' => $posts['description'],
            'category_id' => $posts['category_id']
        ];

        //画像の分岐処理
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            // s3に画像をアップロード
            $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
            // s3の画像URLを取得
            $url = Storage::url($path);
            // 画像のURLをDBに保存
            $update_array['image'] = $url;
        }

        try {
            DB::beginTransaction();
            Recipe::where('id', $id)->update($update_array);
            Ingredient::where('recipe_id', $id)->delete();
            $ingredients = [];
            foreach ($posts['ingredients'] as $key => $ingredient) {
                $ingredients[$key] = [
                    'recipe_id' => $id,
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity']
                ];
            }
            Ingredient::insert($ingredients);
            Step::where('recipe_id', $id)->delete();
            $steps = [];
            foreach ($posts['steps'] as $key => $step) {
                $steps[$key] = [
                    'recipe_id' => $id,
                    'step_number' => $key + 1,
                    'description' => $step
                ];
            }
            Step::insert($steps);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::debug(print_r($th->getMessage(), true));
            throw $th;
        }
        return redirect()
            ->route('recipe.show', ['id' => $id])
            ->with('feedback.success', 'レシピを更新しました!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Recipe::where('id', $id)->delete();
        // 論理削除なので、以下の記述と同じ
        // Recipe::where('id', $id)->update(['deleted_at' => now()]);

        return redirect()
            ->route('home')
            ->with('feedback.warning', 'レシピを削除しました!');
    }
}
