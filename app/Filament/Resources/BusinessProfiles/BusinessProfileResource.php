<?php

namespace App\Filament\Resources\BusinessProfiles;

use App\Filament\Resources\BusinessProfiles\Pages\ManageBusinessProfiles;
use App\Models\BusinessProfile;
use App\Services\GoogleBusinessProfileService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class BusinessProfileResource extends Resource
{
    protected static ?string $model = BusinessProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Google Business Profile';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'business_name';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny() && BusinessProfile::query()->doesntExist();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Business details')->columns(2)->schema([
                TextInput::make('business_name')->required()->maxLength(255),
                TextInput::make('phone')->tel()->maxLength(50),
                TextInput::make('website')->url()->maxLength(255),
                Textarea::make('address')->rows(2)->columnSpanFull(),
                Textarea::make('description')->rows(4)->maxLength(750)->columnSpanFull(),
            ]),
            Section::make('Hours and services')->columns(2)->schema([
                KeyValue::make('regular_hours')->label('Business hours')->keyLabel('Day (Monday-Sunday)')->valueLabel('Hours (09:00-18:00 or Closed)')->columnSpanFull(),
                KeyValue::make('special_hours')->label('Holiday hours')->keyLabel('Date (YYYY-MM-DD)')->valueLabel('Hours or Closed')->columnSpanFull(),
                TagsInput::make('services')->label('Services')->placeholder('Add a service and press Enter')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('business_name')->searchable()->weight(FontWeight::SemiBold),
            TextColumn::make('phone'),
            TextColumn::make('website')->url(fn (BusinessProfile $record): ?string => $record->website)->openUrlInNewTab(),
            TextColumn::make('updated_at')->since()->label('Last updated'),
        ])->recordActions([
            EditAction::make(),
            Action::make('publishToGoogle')->label('Publish profile changes')->icon(Heroicon::OutlinedCloudArrowUp)->color('success')->visible(fn (): bool => auth()->user()?->canManageGoogle() ?? false)->requiresConfirmation()->action(function (BusinessProfile $record, GoogleBusinessProfileService $google): void {
                try {
                    $google->updateProfile($record);
                    Notification::make()->success()->title('Business profile updated on Google')->send();
                } catch (Throwable $exception) {
                    Notification::make()->danger()->title('Google update failed')->body($exception->getMessage())->send();
                }
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBusinessProfiles::route('/')];
    }
}
