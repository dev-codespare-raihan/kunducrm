<?php

namespace App\Filament\Resources\WebsiteCars;

use App\Filament\Resources\WebsiteCars\Pages\ManageWebsiteCars;
use App\Models\WebsiteCar;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WebsiteCarResource extends Resource
{
    protected static ?string $model = WebsiteCar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Website Content';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Cars';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Car details')->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(160),
                    TextInput::make('type')->label('Vehicle type')->required()->maxLength(120),
                    TextInput::make('seats')->required()->maxLength(40),
                    TextInput::make('fuel')->required()->maxLength(80),
                    TextInput::make('local_label')->label('Starting price')->required()->maxLength(80),
                    TextInput::make('best_for')->label('Best for')->required()->maxLength(160),
                    Textarea::make('blurb')->required()->rows(3)->columnSpanFull(),
                ])->columns(2),
                Section::make('Images and search')->schema([
                    FileUpload::make('image_path')->label('Car image')->image()->imageEditor()->imageEditorAspectRatioOptions([null, '16:9', '4:3', '1:1'])->previewable(false)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(8192)->disk('public')->directory('website/cars')->visibility('public'),
                    TextInput::make('image_url')->label('Existing website image URL')->url()->helperText('Used until you upload a replacement image.'),
                    TextInput::make('image_alt')->label('Image alt text')->required()->maxLength(255),
                    TextInput::make('image_width')->numeric()->default(1200)->required(),
                    TextInput::make('image_height')->numeric()->default(675)->required(),
                ])->columns(2),
                Section::make('Publishing')->schema([
                    TextInput::make('sort_order')->numeric()->default(0)->required(),
                    Toggle::make('is_published')->default(true)->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->disk('public')->square()->size(56),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('local_label')->label('Starting price'),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWebsiteCars::route('/'),
        ];
    }
}
