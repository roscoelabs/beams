<form action="<?=\Idno\Core\site()->config()->getDisplayURL()?>following/add" method="post">
    <p>
        Add the address of a site to follow:<br>
            <input type="text" name="url" value="" placeholder="http:// ..." style="width: 100%"><br>
        <input type="submit" class="btn btn-primary" value="Add" style="width: 15%">
        <?=\Idno\Core\site()->actions()->signForm('following/add')?>
    </p>
</form>