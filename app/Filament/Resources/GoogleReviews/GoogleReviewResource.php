<?php

namespace App\Filament\Resources\GoogleReviews;

use App\Filament\Resources\GoogleReviews\Pages\ManageGoogleReviews;
use App\Models\GoogleReview;
use App\Services\GoogleBusinessProfileService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class GoogleReviewResource extends Resource
{
    protected static ?string $model = GoogleReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Google Business Profile';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reviewer_name';

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManageGoogle() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reviewer_name')->label('Reviewer')->searchable()->weight(FontWeight::SemiBold),
            TextColumn::make('star_rating')->label('Rating')->badge()->formatStateUsing(fn (?string $state): string => match ($state) {
                'ONE' => '★☆☆☆☆', 'TWO' => '★★☆☆☆', 'THREE' => '★★★☆☆', 'FOUR' => '★★★★☆', 'FIVE' => '★★★★★', default => $state ?? '—'
            }),
            TextColumn::make('comment')->label('Review')->limit(100)->wrap()->placeholder('No written comment'),
            TextColumn::make('reviewed_at')->label('Date')->date('d M Y')->sortable(),
            TextColumn::make('reply')->label('Reply status')->state(fn (GoogleReview $record): string => filled($record->reply) ? 'Replied' : 'Needs reply')->badge()->color(fn (string $state): string => $state === 'Replied' ? 'success' : 'warning'),
        ])
            ->filters([SelectFilter::make('star_rating')->options(['FIVE' => '5 stars', 'FOUR' => '4 stars', 'THREE' => '3 stars', 'TWO' => '2 stars', 'ONE' => '1 star'])])
            ->recordActions([
                Action::make('reply')->label(fn (GoogleReview $record): string => filled($record->reply) ? 'Edit reply' : 'Reply')->icon(Heroicon::OutlinedChatBubbleLeftRight)->schema([
                    Textarea::make('reply')->label('Google reply')->required()->maxLength(4096)->rows(5)->default(fn (GoogleReview $record): string => $record->reply ?: (in_array($record->star_rating, ['ONE', 'TWO', 'THREE'], true) ? 'We are sorry your experience did not meet expectations. Our manager will contact you shortly.' : 'Thank you for choosing Kundu Tour and Travels. We appreciate your kind review!')),
                ])->action(function (GoogleReview $record, array $data, GoogleBusinessProfileService $google): void {
                    try {
                        $google->replyToReview($record, $data['reply']);
                        Notification::make()->success()->title('Reply published on Google')->send();
                    } catch (Throwable $exception) {
                        Notification::make()->danger()->title('Could not publish reply')->body($exception->getMessage())->send();
                    }
                })->visible(fn (): bool => auth()->user()?->canManageGoogle() ?? false),
                Action::make('deleteReply')->label('Delete reply')->color('danger')->icon(Heroicon::OutlinedTrash)->requiresConfirmation()->action(function (GoogleReview $record, GoogleBusinessProfileService $google): void {
                    try {
                        $google->deleteReviewReply($record);
                        Notification::make()->success()->title('Google reply deleted')->send();
                    } catch (Throwable $exception) {
                        Notification::make()->danger()->title('Could not delete reply')->body($exception->getMessage())->send();
                    }
                })->visible(fn (GoogleReview $record): bool => filled($record->reply) && (auth()->user()?->canManageGoogle() ?? false)),
            ])
            ->defaultSort('reviewed_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGoogleReviews::route('/')];
    }
}
