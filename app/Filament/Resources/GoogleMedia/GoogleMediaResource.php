<?php

namespace App\Filament\Resources\GoogleMedia;

use App\Filament\Resources\GoogleMedia\Pages\ManageGoogleMedia;
use App\Models\GoogleMedia;
use App\Services\GoogleBusinessProfileService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Throwable;

class GoogleMediaResource extends Resource
{
    protected static ?string $model = GoogleMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Google Business Profile';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'google_resource_name';

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManageGoogle() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')->options(['exterior' => 'Exterior', 'interior' => 'Interior', 'team' => 'Team', 'car' => 'Car photos'])->default('car')->required(),
            Select::make('media_format')->label('File type')->options(['PHOTO' => 'Photo', 'VIDEO' => 'Video'])->default('PHOTO')->required(),
            FileUpload::make('local_path')->label('Photo or video')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime'])->maxSize(30720)->directory('google-business/media')->disk('public')->visibility('public')->required(),
            Hidden::make('google_resource_name')->default(fn (): string => 'local-'.Str::uuid())->required(),
            Hidden::make('google_synced_at')->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('google_url')->label('Photo')->state(fn (GoogleMedia $record): ?string => $record->google_url ?: $record->local_path)->disk('public')->square()->size(56),
            TextColumn::make('category')->badge(),
            TextColumn::make('media_format')->label('Type')->placeholder('Photo'),
            TextColumn::make('google_synced_at')->since()->label('Last synced')->sortable(),
        ])->recordActions([
            Action::make('publish')->label('Upload to Google')->icon(Heroicon::OutlinedCloudArrowUp)->color('success')->requiresConfirmation()->action(function (GoogleMedia $record, GoogleBusinessProfileService $google): void {
                try {
                    $google->publishMedia($record);
                    Notification::make()->success()->title('Photo uploaded to Google')->send();
                } catch (Throwable $exception) {
                    Notification::make()->danger()->title('Photo upload failed')->body($exception->getMessage())->send();
                }
            })->visible(fn (GoogleMedia $record): bool => filled($record->local_path) && str_starts_with($record->google_resource_name, 'local-') && (auth()->user()?->canManageGoogle() ?? false)),
            Action::make('deleteFromGoogle')->label('Delete from Google')->color('danger')->icon(Heroicon::OutlinedTrash)->requiresConfirmation()->action(function (GoogleMedia $record, GoogleBusinessProfileService $google): void {
                try {
                    $google->deleteMedia($record);
                    Notification::make()->success()->title('Google photo deleted')->send();
                } catch (Throwable $exception) {
                    Notification::make()->danger()->title('Google photo could not be deleted')->body($exception->getMessage())->send();
                }
            })->visible(fn (GoogleMedia $record): bool => ! str_starts_with($record->google_resource_name, 'local-') && (auth()->user()?->canManageGoogle() ?? false)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGoogleMedia::route('/')];
    }
}
