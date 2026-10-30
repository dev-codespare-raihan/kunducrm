<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Rental CRM';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Select::make('source')->options(['phone' => 'Phone', 'website' => 'Website', 'google' => 'Google Business Profile', 'walk_in' => 'Walk-in', 'referral' => 'Referral', 'other' => 'Other'])->default('phone')->required(),
                Select::make('status')
                    ->options(LeadStatus::class)
                    ->default('new')
                    ->required(),
                Select::make('trip_type')->options(['local' => 'Local', 'airport' => 'Airport transfer', 'outstation' => 'Outstation']),
                TextInput::make('vehicle_category')->placeholder('Sedan, SUV, Innova...'),
                TextInput::make('pickup_location'),
                TextInput::make('destination'),
                DateTimePicker::make('pickup_at')->native(false),
                DateTimePicker::make('return_at')->native(false)->after('pickup_at'),
                TextInput::make('passengers')->numeric()->minValue(1),
                TextInput::make('budget')->numeric()->prefix('₹')->minValue(0),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Select::make('assigned_to')->label('Assigned to')->relationship('assignee', 'name')->searchable()->preload(),
                Select::make('customer_id')
                    ->relationship('customer', 'name'),
                DateTimePicker::make('last_contacted_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->weight(FontWeight::SemiBold)->searchable()->sortable(),
                TextColumn::make('phone')->searchable()->copyable(),
                TextColumn::make('pickup_at')->label('Trip date')->dateTime('d M Y, H:i')->sortable()->placeholder('Not scheduled'),
                TextColumn::make('destination')->placeholder('—')->limit(24),
                TextColumn::make('source')->badge(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('assignee.name')->label('Owner')->placeholder('Unassigned'),
                TextColumn::make('created_at')->since()->label('Added')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(LeadStatus::class),
                SelectFilter::make('source')->options(['phone' => 'Phone', 'website' => 'Website', 'google' => 'Google', 'walk_in' => 'Walk-in', 'referral' => 'Referral', 'other' => 'Other']),
            ])
            ->recordActions([
                Action::make('convertToCustomer')->label('Convert to customer')->icon(Heroicon::OutlinedArrowPathRoundedSquare)->color('success')->visible(fn (Lead $record): bool => ! $record->customer_id)->action(function (Lead $record): void {
                    DB::transaction(function () use ($record): void {
                        $customer = Customer::query()->firstOrCreate(
                            ['phone' => $record->phone],
                            ['name' => $record->name, 'email' => $record->email],
                        );

                        $record->update(['customer_id' => $customer->id, 'status' => LeadStatus::Converted]);
                    });
                })->successNotificationTitle('Lead converted to customer'),
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
            'index' => ManageLeads::route('/'),
        ];
    }
}
