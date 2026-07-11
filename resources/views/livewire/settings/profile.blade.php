<div>
    <form wire:submit="updateProfileInformation">
        <input type="text" wire:model="name" />
        <input type="email" wire:model="email" />
        <button type="submit">Update</button>
    </form>
</div>
