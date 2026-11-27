---
name: doctrine-persistence
description: Model Doctrine entities, relational mappings, QueryBuilder repositories, and reversible migrations.
---

# Skill: Persistence with Doctrine ORM and DBAL (Doctrine Persistence)

## 1. Objective and Responsibility
Model relational database schemas, Doctrine entities, native attribute mappings, repositories, high-performance DQL/QueryBuilder queries, transactional boundaries, and safe, reversible migrations with technical rigor.

## 2. Scope and Limitations
- **Scope:** Entity mapping with native attributes (`#[ORM\...]`), relationships (OneToMany, ManyToOne, ManyToMany), relational constraints, query repositories, DTO projections, and migrations via `doctrine:migrations:diff`.
- **Limitations:** Never execute destructive migrations in production without explicit user authorization.

## 3. Required Technical Knowledge
- Doctrine ORM 2.x / 3.x and DBAL: Identity Map, UnitOfWork, entity states (Managed, Detached, Removed), Naming Strategies.
- PHP 8 native mapping attributes: `#[ORM\Entity]`, `#[ORM\Table]`, `#[ORM\Id]`, `#[ORM\GeneratedValue]`, `#[ORM\Column]`, `#[ORM\ManyToOne]`, `#[ORM\OneToMany]`.
- Loading strategies: `FetchMode::LAZY` vs `FetchMode::EAGER`, fetch joins using `addSelect()`.
- Migrations: `doctrine/doctrine-migrations-bundle`, commands `doctrine:migrations:diff`, `doctrine:migrations:migrate`.

## 4. Development Standards and Conventions
- Database table and column names must use `snake_case`; entity class names use `PascalCase`; properties use `camelCase`.
- Entities must protect their own invariants: `private` properties with typed getters; state mutations performed via semantic domain methods (e.g., `$order->confirm()`) instead of unconstrained public setters.
- Always declare strict column types in `#[ORM\Column(type: Types::STRING, length: 150, nullable: false)]`.
- For dates and timestamps, use exclusively `\DateTimeImmutable` (`Types::DATETIME_IMMUTABLE`).

## 5. Architectural Rules and Code Organization
- Entities reside in `src/Entity/`.
- Repositories reside in `src/Repository/` and must extend `ServiceEntityRepository`.
- Migrations reside in `migrations/`.
- Repositories concentrate database queries; never place HTTP, presentation, or serialization concerns inside repositories.

## 6. PHP and Symfony Best Practices
- **Relationships and Owning Side:** In `ManyToOne` and `OneToMany` relationships, the `ManyToOne` side (which holds the foreign key column) is ALWAYS the owning side (`inversedBy`). The `OneToMany` side is the inverse side (`mappedBy`).
- **Prevent Accidental Cascades:** Never add `cascade: ['remove']` to ManyToOne or ManyToMany relationships (e.g., doing so on an Order-to-Customer relation would delete the customer when deleting an order!).
- **Doctrine Collections:** Initialize `Collection` properties in entity constructors using `new ArrayCollection()`.

## 7. Examples of Correct and Incorrect Implementations

### Example: Order Entity and OneToMany / ManyToOne Relationship
**Incorrect (Anemic setters, mutable DateTime, and dangerous cascade remove):**
```php
// Incorrect: Anemic setters, mutable DateTime, and dangerous cascade remove
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Order
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public int $id;

    // Incorrect: Mutable DateTime can be modified externally without ORM detection!
    #[ORM\Column(type: 'datetime')]
    public \DateTime $createdAt;

    // Critical danger: deleting the order will delete the CUSTOMER from the database!
    #[ORM\ManyToOne(targetEntity: Customer::class, cascade: ['remove'])]
    public Customer $customer;
}
```

**Correct (Strict encapsulation, DateTimeImmutable, and protected relational integrity):**
```php
namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\Index(columns: ['status'], name: 'idx_orders_status')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Customer $customer;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
        $this->status = 'DRAFT';
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Product $product, int $quantity, int $unitPrice): void
    {
        if ($this->status !== 'DRAFT') {
            throw new \DomainException('Cannot add items to a confirmed or cancelled order.');
        }

        $this->items->add(new OrderItem($this, $product, $quantity, $unitPrice));
    }

    public function confirm(): void
    {
        if ($this->items->isEmpty()) {
            throw new \DomainException('An order cannot be confirmed without items.');
        }

        $this->status = 'CONFIRMED';
    }
}
```

## 8. Technical Decision Criteria
- **When to use `orphanRemoval: true`:** Only when the child entity has no existence outside the parent aggregate root (e.g., `OrderItem` within `Order`). If an item is removed from the collection, it should be deleted from the database.
- **When to use explicit transactions:** Whenever performing multiple interdependent persist operations or acquiring locks for concurrent updates (`wrapInTransaction`).

## 9. Validation and Testing Procedures
- Validate Doctrine mapping integrity:
  ```bash
  php bin/console doctrine:schema:validate
  ```
- Generate and inspect migrations:
  ```bash
  php bin/console doctrine:migrations:diff
  ```
- Write integration tests using isolated databases (`KernelTestCase`) verifying relational constraints and cascade behavior.

## 10. Error Handling and Exceptions
- Database constraint violations (`UniqueConstraintViolationException`, `ForeignKeyConstraintViolationException`) must be caught and converted into user-friendly domain exceptions.

## 11. Objective Quality and Completion Criteria
- `doctrine:schema:validate` returns full success (mapping and database sync valid).
- Generated migration files include a functional `down()` method reversing all schema changes.
- Zero raw concatenated queries in repositories.
