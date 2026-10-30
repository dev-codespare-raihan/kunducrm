<?php

namespace App\Filament\Pages;

use App\Services\WebsiteSettingsService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WebsiteSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.website-settings';

    protected static ?string $slug = 'website-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Website';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(WebsiteSettingsService $settings): void
    {
        $this->form->fill($settings->get());
    }

    public function form(Schema $schema): Schema
    {
        $defaults = app(WebsiteSettingsService::class)->defaults();

        return $schema
            ->components([
                Section::make('Website identity')
                    ->description('The name, tagline and images used throughout the public website.')
                    ->schema([
                        TextInput::make('site_name')->label('Website name')->required()->maxLength(120),
                        TextInput::make('tagline')->maxLength(180),
                        Grid::make(2)->schema([
                            FileUpload::make('logo_path')
                                ->label('Logo')
                                ->image()
                                ->imageEditor()
                                ->imageEditorAspectRatioOptions([null, '3:1', '16:9', '1:1'])
                                ->previewable(false)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                                ->maxSize(4096)
                                ->disk('public')
                                ->directory('website/branding')
                                ->visibility('public')
                                ->helperText('Crop the image in the editor before saving. The current saved logo is shown above.'),
                            FileUpload::make('favicon_path')
                                ->label('Favicon')
                                ->image()
                                ->imageEditor()
                                ->imageEditorAspectRatioOptions([null, '1:1'])
                                ->previewable(false)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/x-icon'])
                                ->maxSize(1024)
                                ->disk('public')
                                ->directory('website/branding')
                                ->visibility('public')
                                ->helperText('Use a square crop for the clearest favicon.'),
                        ]),
                    ])->columns(2),
                Section::make('Search engine settings')
                    ->schema([
                        TextInput::make('seo_title')->label('Default page title')->required()->maxLength(255),
                        Textarea::make('seo_description')->rows(3)->required()->maxLength(500),
                        TextInput::make('seo_keywords')->label('Keywords')->maxLength(500),
                        Toggle::make('robots_index')->label('Allow search engines to index the website')->default(true),
                    ]),
                Section::make('Cars page content and SEO')->description('Text displayed on the public cars page, plus its search title and description.')->schema([
                    TextInput::make('cars_page.eyebrow')->required()->maxLength(120),
                    TextInput::make('cars_page.title')->required()->maxLength(255),
                    Textarea::make('cars_page.intro')->required()->rows(3)->columnSpanFull(),
                    Textarea::make('cars_page.details')->required()->rows(3)->columnSpanFull(),
                    TextInput::make('cars_page.special_title')->required()->maxLength(160),
                    Textarea::make('cars_page.special_text')->required()->rows(3)->columnSpanFull(),
                    TextInput::make('cars_page.fleet_title')->required()->maxLength(160),
                    TextInput::make('cars_page.pricing_title')->required()->maxLength(160),
                    Repeater::make('cars_page.price_notes')->simple(TextInput::make('note')->required())->label('Pricing notes')->columnSpanFull(),
                    TextInput::make('cars_page.seo_title')->required()->maxLength(255),
                    Textarea::make('cars_page.seo_description')->required()->rows(2)->maxLength(500)->columnSpanFull(),
                ])->columns(2),
                Section::make('Tour packages page content and SEO')->description('Introductory text, link label and search metadata for the tour packages page.')->schema([
                    TextInput::make('tours_page.eyebrow')->required()->maxLength(120),
                    TextInput::make('tours_page.title')->required()->maxLength(255),
                    Textarea::make('tours_page.intro')->required()->rows(3)->columnSpanFull(),
                    Textarea::make('tours_page.details')->required()->rows(3)->columnSpanFull(),
                    TextInput::make('tours_page.outstation_link_label')->required()->maxLength(160),
                    TextInput::make('tours_page.seo_title')->required()->maxLength(255),
                    Textarea::make('tours_page.seo_description')->required()->rows(2)->maxLength(500)->columnSpanFull(),
                ])->columns(2),
                Section::make('Website colors')
                    ->description('These colors replace the matching site-wide design tokens.')
                    ->schema([
                        Grid::make(3)->schema(collect($defaults['colors'])->map(fn (string $color, string $key): ColorPicker => ColorPicker::make("colors.{$key}")->label(str($key)->replace('_', ' ')->title())->hex()->required())->all()),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(WebsiteSettingsService $settings): void
    {
        $data = $this->form->getState();
        $old = $settings->get();

        try {
            $settings->save($data);
            foreach (['logo_path', 'favicon_path'] as $path) {
                if (filled($old[$path] ?? null) && ($old[$path] !== ($data[$path] ?? null))) {
                    Storage::disk('public')->delete($old[$path]);
                }
            }
            Notification::make()->success()->title('Website settings saved')->body('The public website will use the updated settings within five minutes.')->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->danger()->title('Website settings could not be saved')->body('Please review the fields and try again.')->send();
        }
    }
}
