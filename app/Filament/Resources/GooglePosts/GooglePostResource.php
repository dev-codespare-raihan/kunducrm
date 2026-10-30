<?php

namespace App\Filament\Resources\GooglePosts;

use App\Filament\Resources\GooglePosts\Pages\ManageGooglePosts;
use App\Models\GooglePost;
use App\Services\GoogleBusinessProfileService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class GooglePostResource extends Resource
{
    protected static ?string $model = GooglePost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|\UnitEnum|null $navigationGroup = 'Google Business Profile';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManageGoogle() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Google post')->columns(2)->schema([
                TextInput::make('title')->required()->maxLength(100),
                Select::make('post_type')->options(['standard' => "What's new", 'offer' => 'Offer', 'event' => 'Event'])->default('standard')->required()->live(),
                Textarea::make('summary')->required()->maxLength(1500)->rows(5)->columnSpanFull(),
                FileUpload::make('photo_path')->label('Photo')->image()->maxSize(10240)->directory('google-business/posts')->disk('public')->visibility('public')->imageEditor(),
                Select::make('call_to_action')->options(['LEARN_MORE' => 'Learn more', 'BOOK' => 'Book', 'CALL' => 'Call now'])->native(false),
                TextInput::make('call_to_action_url')->label('Button URL')->url()->visible(fn (Get $get): bool => filled($get('call_to_action')) && $get('call_to_action') !== 'CALL'),
                DateTimePicker::make('scheduled_at')->label('Publish at')->native(false)->helperText('Leave blank to publish immediately.'),
            ]),
            Section::make('Offer details')->columns(2)->visible(fn (Get $get): bool => $get('post_type') === 'offer')->schema([
                DatePicker::make('offer_start_date')->required(),
                DatePicker::make('offer_end_date')->required()->afterOrEqual('offer_start_date'),
                TextInput::make('coupon_code')->maxLength(50),
            ]),
            Section::make('Event dates')->columns(2)->visible(fn (Get $get): bool => $get('post_type') === 'event')->schema([
                DateTimePicker::make('event_start_at')->native(false)->required(),
                DateTimePicker::make('event_end_at')->native(false)->after('event_start_at')->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->weight(FontWeight::SemiBold),
            TextColumn::make('post_type')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                'standard' => "What's new", 'offer' => 'Offer', 'event' => 'Event', default => $state
            }),
            TextColumn::make('scheduled_at')->dateTime('d M Y, H:i')->sortable()->placeholder('Publish now'),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('published_at')->dateTime('d M Y, H:i')->placeholder('—'),
        ])
            ->filters([SelectFilter::make('status')->options(['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published', 'deleted' => 'Deleted'])])
            ->recordActions([
                Action::make('publish')->label(fn (GooglePost $record): string => $record->scheduled_at ? 'Schedule on Google' : 'Publish to Google')->icon(Heroicon::OutlinedPaperAirplane)->color('success')->requiresConfirmation()->action(function (GooglePost $record, GoogleBusinessProfileService $google): void {
                    try {
                        $google->publishPost($record);
                        Notification::make()->success()->title($record->scheduled_at ? 'Post scheduled on Google' : 'Post published on Google')->send();
                    } catch (Throwable $exception) {
                        Notification::make()->danger()->title('Post could not be published')->body($exception->getMessage())->send();
                    }
                })->visible(fn (GooglePost $record): bool => in_array($record->status, ['draft'], true) && (auth()->user()?->canManageGoogle() ?? false)),
                EditAction::make()->visible(fn (GooglePost $record): bool => $record->status === 'draft'),
                DeleteAction::make()->visible(fn (GooglePost $record): bool => $record->status === 'draft'),
                Action::make('deleteFromGoogle')->label('Delete on Google')->color('danger')->icon(Heroicon::OutlinedTrash)->requiresConfirmation()->action(function (GooglePost $record, GoogleBusinessProfileService $google): void {
                    try {
                        $google->deletePost($record);
                        Notification::make()->success()->title('Post deleted from Google')->send();
                    } catch (Throwable $exception) {
                        Notification::make()->danger()->title('Google post could not be deleted')->body($exception->getMessage())->send();
                    }
                })->visible(fn (GooglePost $record): bool => filled($record->google_resource_name) && $record->status !== 'deleted' && (auth()->user()?->canManageGoogle() ?? false)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGooglePosts::route('/')];
    }
}
