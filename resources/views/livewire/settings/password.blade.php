<div>
    <form wire:submit="updatePassword">
        <input type="password" wire:model="current_password" />
        <input type="password" wire:model="password" />
        <input type="password" wire:model="password_confirmation" />
        <button type="submit">Update</button>
    </form>
</div>
