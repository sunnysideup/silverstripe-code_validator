see code.

```php


class MyObject extends DataObject 
{
    protected function onBeforeWrite() 
    {
        parent::onBeforeWrite();
        $obj = new ValidateCodeFieldForObject();
        $obj->CodeField = $obj->checkCode("CodeField", $this);
    }
}

```
